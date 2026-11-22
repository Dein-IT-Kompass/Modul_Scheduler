<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointment_series_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer', 'id_services' => 'integer', 'id_users_provider' => 'integer',
        'recurrence_interval' => 'integer', 'duration_minutes' => 'integer',
        'occurrence_count' => 'integer', 'capacity' => 'integer',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('working_plan_exceptions_model');
    }

    public function validate(array $series): void
    {
        foreach (['name', 'id_services', 'id_users_provider', 'start_date', 'slots'] as $field) {
            if (empty($series[$field])) throw new InvalidArgumentException('Missing series field: ' . $field);
        }
        if (!is_array($series['slots']) || count($series['slots']) < 1) {
            throw new InvalidArgumentException('At least one weekly slot is required.');
        }
        foreach ($series['slots'] as $slot) {
            if (!isset($slot['weekday'], $slot['start_time'], $slot['duration_minutes'])) {
                throw new InvalidArgumentException('Incomplete weekly slot.');
            }
            if ((int) $slot['weekday'] < 0 || (int) $slot['weekday'] > 6 || (int) $slot['duration_minutes'] < 1) {
                throw new InvalidArgumentException('Invalid weekly slot.');
            }
        }
        $end_type = $series['end_type'] ?? 'weeks';
        if (!in_array($end_type, ['weeks', 'count', 'date'], true)) throw new InvalidArgumentException('Invalid end type.');
        if ($end_type === 'weeks' && (int) ($series['week_count'] ?? 0) < 1) throw new InvalidArgumentException('Week count required.');
        if ($end_type === 'count' && (int) ($series['occurrence_count'] ?? 0) < 1) throw new InvalidArgumentException('Occurrence count required.');
        if ($end_type === 'date' && empty($series['end_date'])) throw new InvalidArgumentException('End date required.');
        if (!in_array($series['course_state'] ?? 'tentative', ['tentative', 'confirmed', 'cancelled'], true)) throw new InvalidArgumentException('Invalid course state.');
    }

    public function generate_occurrences(array $series): array
    {
        $this->validate($series);
        $start = new DateTime($series['start_date'] . ' 00:00:00');
        $anchor_weekday = (int) $start->format('w');
        $end_type = $series['end_type'] ?? 'weeks';
        $week_limit = $end_type === 'weeks' ? min(104, (int) $series['week_count']) : 520;
        $count_limit = $end_type === 'count' ? min(1000, (int) $series['occurrence_count']) : 1000;
        $end_date = $end_type === 'date' ? new DateTime($series['end_date'] . ' 23:59:59') : null;
        $interval = max(1, (int) ($series['recurrence_interval'] ?? 1));
        $result = [];

        $slots = $series['slots'];
        usort($slots, static function ($a, $b) use ($anchor_weekday) {
            $a_delta = ((int) $a['weekday'] - $anchor_weekday + 7) % 7;
            $b_delta = ((int) $b['weekday'] - $anchor_weekday + 7) % 7;
            return [$a_delta, $a['start_time']] <=> [$b_delta, $b['start_time']];
        });

        for ($week = 0; $week < $week_limit && count($result) < $count_limit; $week++) {
            $week_start = (clone $start)->modify('+' . ($week * $interval * 7) . ' days');
            foreach ($slots as $slot) {
                $delta = ((int) $slot['weekday'] - $anchor_weekday + 7) % 7;
                $date = (clone $week_start)->modify('+' . $delta . ' days');
                $begin = new DateTime($date->format('Y-m-d') . ' ' . $slot['start_time']);
                if ($begin < $start || ($end_date && $begin > $end_date)) continue;
                $end = (clone $begin)->modify('+' . (int) $slot['duration_minutes'] . ' minutes');
                $result[] = [
                    'sequence' => count($result) + 1,
                    'week_number' => $week + 1,
                    'weekday' => (int) $slot['weekday'],
                    'start_datetime' => $begin->format('Y-m-d H:i:s'),
                    'end_datetime' => $end->format('Y-m-d H:i:s'),
                ];
                if (count($result) >= $count_limit) break 2;
            }
        }
        return $result;
    }

    public function preview(array $series): array
    {
        $participantIds = array_values(
            array_unique(
                array_filter([
                    (int) $series['id_users_provider'],
                    ...array_map(
                        'intval',
                        $series['employee_ids'] ?? [],
                    ),
                ]),
            ),
        );

        $participants = [];

        foreach ($participantIds as $participantId) {
            $participant = $this->providers_model->find($participantId);

            $participants[] = [
                'provider' => $participant,
                'name' => trim(
                    ($participant['first_name'] ?? '') .
                    ' ' .
                    ($participant['last_name'] ?? ''),
                ),
                'working_plan' => json_decode(
                    $participant['settings']['working_plan'] ?? '{}',
                    true,
                ) ?: [],
                'exceptions' => $this->working_plan_exceptions_model
                    ->get_by_provider($participantId),
            ];
        }

        $occurrences = $this->generate_occurrences($series);

        foreach ($occurrences as &$occurrence) {
            $occurrence['available'] = true;
            $occurrence['reason_code'] = null;
            $occurrence['reason'] = 'Alle Mitarbeiter verfügbar';
            $occurrence['participant_availability'] = [];

            foreach ($participants as $participant) {
                $availability = $this->check_provider_availability(
                    $occurrence,
                    $participant['provider'],
                    $participant['working_plan'],
                    $participant['exceptions'],
                );

                $occurrence['participant_availability'][] = [
                    'id_users' => (int) $participant['provider']['id'],
                    'name' => $participant['name'],
                    'available' => $availability['available'],
                    'reason_code' => $availability['reason_code'],
                    'reason' => $availability['reason'],
                ];

                if (!$availability['available']) {
                    $occurrence['available'] = false;
                    $occurrence['reason_code'] = $availability['reason_code'];
                    $occurrence['reason'] =
                        $participant['name'] .
                        ': ' .
                        $availability['reason'];

                    break;
                }
            }
        }

        unset($occurrence);

        return $occurrences;
    }

    private function check_provider_availability(array $occurrence, array $provider, array $working_plan, array $exceptions): array
    {
        $start = new DateTime($occurrence['start_datetime']);
        $end = new DateTime($occurrence['end_datetime']);
        $date = $start->format('Y-m-d');
        $weekday = strtolower($start->format('l'));
        $plan = $working_plan[$weekday] ?? null;

        if (array_key_exists($date, $exceptions)) {
            $plan = $exceptions[$date];
        }
        if (!$plan || empty($plan['start']) || empty($plan['end'])) {
            return $this->unavailable('outside_working_plan', 'Mitarbeiter an diesem Tag nicht verfügbar');
        }

        $plan_start = new DateTime($date . ' ' . $plan['start']);
        $plan_end = new DateTime($date . ' ' . $plan['end']);
        if ($start < $plan_start || $end > $plan_end) {
            return $this->unavailable('outside_working_plan', 'Außerhalb des Arbeitsplans (' . $plan['start'] . '–' . $plan['end'] . ')');
        }

        foreach ($plan['breaks'] ?? [] as $break) {
            $break_start = new DateTime($date . ' ' . $break['start']);
            $break_end = new DateTime($date . ' ' . $break['end']);
            if ($start < $break_end && $end > $break_start) {
                return $this->unavailable('working_plan_break', 'Überschneidung mit Pause ' . $break['start'] . '–' . $break['end']);
            }
        }

        $overlaps = $this->db
            ->from('appointments')
            ->where('id_users_provider', (int) $provider['id'])
            ->where('start_datetime <', $end->format('Y-m-d H:i:s'))
            ->where('end_datetime >', $start->format('Y-m-d H:i:s'))
            ->get()->result_array();

        if ($overlaps) {
            foreach ($overlaps as $overlap) {
                if ((int) $overlap['is_unavailability'] === 1) {
                    return $this->unavailable('unavailability', 'Mitarbeiter hat eine Nichtverfügbarkeit');
                }
            }
            return $this->unavailable('appointment_conflict', 'Mitarbeiter bereits mit einem Termin belegt');
        }

        return ['available' => true, 'reason_code' => null, 'reason' => 'Verfügbar'];
    }

    private function unavailable(string $code, string $reason): array
    {
        return ['available' => false, 'reason_code' => $code, 'reason' => $reason];
    }

    public function create_with_appointments(array $series): array
    {
        // Re-check immediately before the transaction to prevent stale previews.
        $occurrences = $this->preview($series);
        $conflicts = array_values(array_filter($occurrences, fn($item) => !$item['available']));
        if ($conflicts) {
            $first = $conflicts[0];
            throw new RuntimeException('Serie nicht angelegt: ' . $first['start_datetime'] . ' – ' . $first['reason']);
        }

        $this->db->trans_begin();
        try {
            $now = date('Y-m-d H:i:s');
            $first_slot = $series['slots'][0];
            $record = $series;
            unset(
                $record['slots'],
                $record['week_count'],
                $record['employee_ids'],
            );
            $record['recurrence_type'] = 'weekly';
            $record['start_time'] = $first_slot['start_time'];
            $record['duration_minutes'] = (int) $first_slot['duration_minutes'];
            $record['course_state'] = $record['course_state'] ?? 'tentative';
            $record['create_datetime'] = $now;
            $record['update_datetime'] = $now;
            $this->db->insert('appointment_series', $record);
            $series_id = (int) $this->db->insert_id();

            $this->saveEmployees(
                $series_id,
                (int) $series['id_users_provider'],
                $series['employee_ids'] ?? [],
                $now,
            );

            foreach ($series['slots'] as $order => $slot) {
                $this->db->insert('appointment_series_slots', [
                    'id_appointment_series' => $series_id,
                    'weekday' => (int) $slot['weekday'],
                    'start_time' => $slot['start_time'],
                    'duration_minutes' => (int) $slot['duration_minutes'],
                    'sort_order' => $order,
                    'create_datetime' => $now,
                    'update_datetime' => $now,
                ]);
            }

            $ids = [];
            foreach ($occurrences as $occurrence) {
                $ids[] = $this->appointments_model->save([
                    'start_datetime' => $occurrence['start_datetime'], 'end_datetime' => $occurrence['end_datetime'],
                    'location' => $series['location'] ?? null, 'notes' => $series['notes'] ?? null,
                    'color' => $series['color'] ?? '#7cbae8', 'status' => 'Unter Vorbehalt',
                    'is_unavailability' => 0, 'id_users_provider' => (int) $series['id_users_provider'],
                    'id_users_customer' => null, 'id_services' => (int) $series['id_services'],
                    'capacity' => ($series['capacity_mode'] ?? 'unlimited') === 'limited' ? (int) $series['capacity'] : null, 'capacity_mode' => $series['capacity_mode'] ?? 'unlimited', 'booking_mode' => 'group',
                    'visibility' => $series['visibility'] ?? 'public', 'course_state' => 'tentative',
                    'id_appointment_series' => $series_id, 'series_sequence' => $occurrence['sequence'],
                ]);
            }
            if ($this->db->trans_status() === false) throw new RuntimeException('Serie konnte nicht vollständig angelegt werden.');
            $this->db->trans_commit();
            return ['series_id' => $series_id, 'appointment_ids' => $ids];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    public function get_for_edit(int $series_id): ?array
    {
        $series = $this->db->get_where('appointment_series', ['id' => $series_id])->row_array();
        if (!$series) return null;
        $series['slots'] = $this->db->where('id_appointment_series', $series_id)
            ->order_by('sort_order', 'ASC')->get('appointment_series_slots')->result_array();
        $service = $this->db->select('id_service_categories')->get_where('services', ['id' => $series['id_services']])->row_array();
        $series['id_service_categories'] = (int)($service['id_service_categories'] ?? 0);

        $series['employee_ids'] = array_map(
            'intval',
            array_column(
                $this->db
                    ->select('id_users')
                    ->where('id_appointment_series', $series_id)
                    ->where('role', 'employee')
                    ->get('appointment_series_participants')
                    ->result_array(),
                'id_users',
            ),
        );

        foreach ($series['slots'] as &$slot) {
            $slot['weekday'] = (int)$slot['weekday'];
            $slot['duration_minutes'] = (int)$slot['duration_minutes'];
        }
        unset($slot);
        return $series;
    }

    public function update_with_future_appointments(int $series_id, array $series): array
    {
        $existing = $this->get_for_edit($series_id);
        if (!$existing) throw new RuntimeException('Kursserie nicht gefunden.');
        $this->validate($series);
        $occurrences = $this->preview($series);
        $conflicts = array_values(array_filter($occurrences, fn($item) => !$item['available']));
        if ($conflicts) throw new RuntimeException('Serie nicht aktualisiert: ' . $conflicts[0]['start_datetime'] . ' – ' . $conflicts[0]['reason']);

        $now = date('Y-m-d H:i:s');
        $future = $this->db->where('id_appointment_series', $series_id)->where('start_datetime >=', $now)
            ->order_by('series_sequence', 'ASC')->get('appointments')->result_array();
        $future_by_sequence = [];
        foreach ($future as $appointment) $future_by_sequence[(int)$appointment['series_sequence']] = $appointment;

        $this->db->trans_begin();
        try {
            $first_slot = $series['slots'][0];
            $record = $series;
            unset(
                $record['slots'],
                $record['week_count'],
                $record['capacity_mode'],
                $record['employee_ids'],
            );
            $record['recurrence_type'] = 'weekly';
            $record['start_time'] = $first_slot['start_time'];
            $record['duration_minutes'] = (int)$first_slot['duration_minutes'];
            $record['course_state'] = $record['course_state'] ?? 'tentative';
            $record['update_datetime'] = $now;
            $this->db->where('id', $series_id)->update('appointment_series', $record);

            $this->saveEmployees(
                $series_id,
                (int) $series['id_users_provider'],
                $series['employee_ids'] ?? [],
                $now,
            );

            $this->db->delete('appointment_series_slots', ['id_appointment_series' => $series_id]);
            foreach ($series['slots'] as $order => $slot) {
                $this->db->insert('appointment_series_slots', [
                    'id_appointment_series'=>$series_id,'weekday'=>(int)$slot['weekday'],'start_time'=>$slot['start_time'],
                    'duration_minutes'=>(int)$slot['duration_minutes'],'sort_order'=>$order,
                    'create_datetime'=>$now,'update_datetime'=>$now,
                ]);
            }

            $kept=[]; $created=[]; $updated=[];
            foreach ($occurrences as $occurrence) {
                $seq=(int)$occurrence['sequence'];
                $payload=[
                    'start_datetime'=>$occurrence['start_datetime'],'end_datetime'=>$occurrence['end_datetime'],
                    'location'=>$series['location'] ?? null,'notes'=>$series['notes'] ?? null,
                    'color'=>$series['color'] ?? '#7cbae8','status'=>$series['status'] ?? 'Unter Vorbehalt',
                    'is_unavailability'=>0,'id_users_provider'=>(int)$series['id_users_provider'],'id_users_customer'=>null,
                    'id_services'=>(int)$series['id_services'],
                    'capacity'=>($series['capacity_mode'] ?? 'unlimited') === 'limited' ? (int)$series['capacity'] : null,
                    'capacity_mode'=>$series['capacity_mode'] ?? 'unlimited','booking_mode'=>'group',
                    'visibility'=>$series['visibility'] ?? 'public','course_state'=>$series['course_state'] ?? 'tentative',
                    'id_appointment_series'=>$series_id,'series_sequence'=>$seq,
                ];
                if (isset($future_by_sequence[$seq])) {
                    $id=(int)$future_by_sequence[$seq]['id'];
                    $this->appointments_model->save(['id'=>$id] + $payload); $updated[]=$id; $kept[$id]=true;
                } else {
                    $id=$this->appointments_model->save($payload); $created[]=$id; $kept[$id]=true;
                }
            }

            $skipped=[]; $deleted=[];
            foreach ($future as $old) {
                $id=(int)$old['id']; if (isset($kept[$id])) continue;
                $bookings=$this->db->where('id_appointments',$id)->count_all_results('appointment_bookings');
                if ($bookings>0) { $skipped[]=$id; continue; }
                $this->db->delete('appointments',['id'=>$id]); $deleted[]=$id;
            }
            if ($this->db->trans_status() === false) throw new RuntimeException('Serie konnte nicht vollstaendig aktualisiert werden.');
            $this->db->trans_commit();
            return ['series_id'=>$series_id,'updated_count'=>count($updated),'created_count'=>count($created),'deleted_count'=>count($deleted),'protected_appointment_ids'=>$skipped];
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
    }

    private function saveEmployees(
        int $seriesId,
        int $initiatorId,
        array $employeeIds,
        string $now
    ): void {
        $this->db->delete(
            'appointment_series_participants',
            ['id_appointment_series' => $seriesId],
        );

        $this->db->insert('appointment_series_participants', [
            'id_appointment_series' => $seriesId,
            'id_users' => $initiatorId,
            'role' => 'initiator',
            'availability_required' => 1,
            'create_datetime' => $now,
            'update_datetime' => $now,
        ]);

        $employeeIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $employeeIds),
                    static fn(int $id): bool =>
                        $id > 0 && $id !== $initiatorId,
                ),
            ),
        );

        foreach ($employeeIds as $employeeId) {
            $this->db->insert('appointment_series_participants', [
                'id_appointment_series' => $seriesId,
                'id_users' => $employeeId,
                'role' => 'employee',
                'availability_required' => 1,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]);
        }
    }

}
