<?php defined('BASEPATH') or exit('No direct script access allowed');

class Employee_absences_model extends EA_Model
{
    public function get_for_user(int $user_id): array
    {
        return $this->base_query()
            ->where('employee_absences.id_users_employee', $user_id)
            ->order_by('employee_absences.start_date', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_all(): array
    {
        return $this->base_query()
            ->order_by('employee_absences.start_date', 'DESC')
            ->order_by('employee_absences.id', 'DESC')
            ->get()
            ->result_array();
    }

    public function find(int $absence_id): ?array
    {
        $record = $this->base_query()
            ->where('employee_absences.id', $absence_id)
            ->get()
            ->row_array();

        return $record ?: null;
    }

    public function create(array $absence, int $requesting_user_id): int
    {
        $this->validate_absence($absence);

        $now = date('Y-m-d H:i:s');
        $type = $absence['absence_type'];

        $record = [
            'id_users_employee' => (int) $absence['id_users_employee'],
            'absence_type' => $type,
            'start_date' => $absence['start_date'],
            'end_date' => $absence['end_date'],
            'start_time' => !empty($absence['all_day'])
                ? null
                : ($absence['start_time'] ?? null),
            'end_time' => !empty($absence['all_day'])
                ? null
                : ($absence['end_time'] ?? null),
            'all_day' => !empty($absence['all_day']) ? 1 : 0,
            'status' => $type === 'sick' ? 'approved' : 'requested',
            'notes' => trim((string) ($absence['notes'] ?? '')),
            'requested_by' => $requesting_user_id,
            'requested_at' => $now,
            'decided_by' => $type === 'sick' ? $requesting_user_id : null,
            'decided_at' => $type === 'sick' ? $now : null,
            'decision_note' => $type === 'sick'
                ? 'Krankmeldung automatisch als Abwesenheit aktiviert.'
                : null,
            'create_datetime' => $now,
            'update_datetime' => $now,
        ];

        if (!$this->db->insert('employee_absences', $record)) {
            throw new RuntimeException('Abwesenheit konnte nicht gespeichert werden.');
        }

        return (int) $this->db->insert_id();
    }

    public function decide(
        int $absence_id,
        string $status,
        int $deciding_user_id,
        ?string $decision_note
    ): void {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Ungültiger Entscheidungsstatus.');
        }

        if (!$this->find($absence_id)) {
            throw new RuntimeException('Abwesenheitsantrag nicht gefunden.');
        }

        $this->db
            ->where('id', $absence_id)
            ->update('employee_absences', [
                'status' => $status,
                'decided_by' => $deciding_user_id,
                'decided_at' => date('Y-m-d H:i:s'),
                'decision_note' => trim((string) $decision_note),
                'update_datetime' => date('Y-m-d H:i:s'),
            ]);
    }

    public function cancel(
        int $absence_id,
        int $user_id,
        bool $can_manage_all
    ): void {
        $absence = $this->find($absence_id);

        if (!$absence) {
            throw new RuntimeException('Abwesenheit nicht gefunden.');
        }

        if (
            !$can_manage_all &&
            (int) $absence['id_users_employee'] !== $user_id
        ) {
            throw new RuntimeException(
                'Diese Abwesenheit darf nicht storniert werden.'
            );
        }

        $this->db
            ->where('id', $absence_id)
            ->update('employee_absences', [
                'status' => 'cancelled',
                'update_datetime' => date('Y-m-d H:i:s'),
            ]);
    }

    private function validate_absence(array $absence): void
    {
        $allowed_types = [
            'vacation',
            'sick',
            'training',
            'business_trip',
            'other',
        ];

        if (
            empty($absence['id_users_employee']) ||
            empty($absence['absence_type']) ||
            empty($absence['start_date']) ||
            empty($absence['end_date'])
        ) {
            throw new InvalidArgumentException(
                'Mitarbeiter, Abwesenheitsart, Beginn und Ende sind erforderlich.'
            );
        }

        if (!in_array($absence['absence_type'], $allowed_types, true)) {
            throw new InvalidArgumentException('Ungültige Abwesenheitsart.');
        }

        if ($absence['end_date'] < $absence['start_date']) {
            throw new InvalidArgumentException(
                'Das Enddatum darf nicht vor dem Startdatum liegen.'
            );
        }

        if (
            empty($absence['all_day']) &&
            (
                empty($absence['start_time']) ||
                empty($absence['end_time'])
            )
        ) {
            throw new InvalidArgumentException(
                'Für eine stundenweise Abwesenheit werden Beginn und Ende benötigt.'
            );
        }

        if (
            empty($absence['all_day']) &&
            $absence['start_date'] === $absence['end_date'] &&
            $absence['end_time'] <= $absence['start_time']
        ) {
            throw new InvalidArgumentException(
                'Das Ende der Abwesenheit muss nach dem Beginn liegen.'
            );
        }
    }

    private function base_query()
    {
        return $this->db
            ->select(
                'employee_absences.*,
                 employees.first_name AS employee_first_name,
                 employees.last_name AS employee_last_name,
                 requesters.first_name AS requester_first_name,
                 requesters.last_name AS requester_last_name,
                 deciders.first_name AS decider_first_name,
                 deciders.last_name AS decider_last_name'
            )
            ->from('employee_absences')
            ->join(
                'users AS employees',
                'employees.id = employee_absences.id_users_employee',
                'inner'
            )
            ->join(
                'users AS requesters',
                'requesters.id = employee_absences.requested_by',
                'inner'
            )
            ->join(
                'users AS deciders',
                'deciders.id = employee_absences.decided_by',
                'left'
            );
    }
}
