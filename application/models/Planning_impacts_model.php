<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_impacts_model extends EA_Model
{
    public function rebuild_all(): array
    {
        $detected = 0;
        $resolved = 0;

        $assignments = $this->db
            ->select('id')
            ->where('status !=', 'cancelled')
            ->get('planning_assignments')
            ->result_array();

        foreach ($assignments as $assignment) {
            $result = $this->rebuild_for_assignment(
                (int) $assignment['id']
            );

            $detected += $result['detected'];
            $resolved += $result['resolved'];
        }

        return [
            'detected' => $detected,
            'resolved' => $resolved,
        ];
    }

    public function rebuild_for_project(
        int $project_id
    ): array {
        $detected = 0;
        $resolved = 0;

        $assignments = $this->db
            ->select('id')
            ->where(
                'id_planning_projects',
                $project_id
            )
            ->get('planning_assignments')
            ->result_array();

        foreach ($assignments as $assignment) {
            $result = $this->rebuild_for_assignment(
                (int) $assignment['id']
            );

            $detected += $result['detected'];
            $resolved += $result['resolved'];
        }

        return [
            'detected' => $detected,
            'resolved' => $resolved,
        ];
    }

    public function rebuild_for_assignment(
        int $assignment_id
    ): array {
        $assignment = $this->db
            ->get_where(
                'planning_assignments',
                ['id' => $assignment_id]
            )
            ->row_array();

        if (!$assignment) {
            return [
                'detected' => 0,
                'resolved' => 0,
            ];
        }

        $active_sources = [];
        $detected = 0;

        foreach (
            $this->get_employee_impacts($assignment)
            as $impact
        ) {
            $active_sources[] =
                $impact['source_type'] . ':' .
                $impact['source_id'];

            if (
                $this->upsert_impact(
                    $assignment_id,
                    $impact
                )
            ) {
                $detected++;
            }
        }

        foreach (
            $this->get_employment_impacts($assignment)
            as $impact
        ) {
            $active_sources[] =
                $impact['source_type'] . ':' .
                $impact['source_id'];

            if (
                $this->upsert_impact(
                    $assignment_id,
                    $impact
                )
            ) {
                $detected++;
            }
        }

        foreach (
            $this->get_resource_impacts($assignment)
            as $impact
        ) {
            $active_sources[] =
                $impact['source_type'] . ':' .
                $impact['source_id'];

            if (
                $this->upsert_impact(
                    $assignment_id,
                    $impact
                )
            ) {
                $detected++;
            }
        }

        $resolved = $this->resolve_missing_impacts(
            $assignment_id,
            $active_sources
        );

        return [
            'detected' => $detected,
            'resolved' => $resolved,
        ];
    }

    public function get_by_assignment(
        int $assignment_id
    ): array {
        return $this->db
            ->where(
                'id_planning_assignments',
                $assignment_id
            )
            ->order_by(
                "FIELD(
                    severity,
                    'critical',
                    'warning',
                    'notice'
                )",
                '',
                false
            )
            ->order_by('detected_datetime', 'DESC')
            ->get('planning_assignment_impacts')
            ->result_array();
    }

    public function get_open_by_project(
        int $project_id
    ): array {
        return $this->db
            ->select(
                'planning_assignment_impacts.*,
                 planning_assignments.title AS assignment_title,
                 planning_assignments.start_datetime,
                 planning_assignments.end_datetime'
            )
            ->from('planning_assignment_impacts')
            ->join(
                'planning_assignments',
                'planning_assignments.id =
                    planning_assignment_impacts.id_planning_assignments',
                'inner'
            )
            ->where(
                'planning_assignments.id_planning_projects',
                $project_id
            )
            ->where_in(
                'planning_assignment_impacts.status',
                ['open', 'acknowledged']
            )
            ->order_by(
                "FIELD(
                    planning_assignment_impacts.severity,
                    'critical',
                    'warning',
                    'notice'
                )",
                '',
                false
            )
            ->order_by(
                'planning_assignments.start_datetime',
                'ASC'
            )
            ->get()
            ->result_array();
    }

    public function decide(
        int $impact_id,
        string $decision,
        string $notes = ''
    ): void {
        $allowed = [
            'replace',
            'continue',
            'reschedule',
            'cancel',
            'not_critical',
        ];

        if (!in_array($decision, $allowed, true)) {
            throw new InvalidArgumentException(
                'Die gewählte Entscheidung ist ungültig.'
            );
        }

        $impact = $this->db
            ->get_where(
                'planning_assignment_impacts',
                ['id' => $impact_id]
            )
            ->row_array();

        if (!$impact) {
            throw new RuntimeException(
                'Beeinträchtigung wurde nicht gefunden.'
            );
        }

        $status = in_array(
            $decision,
            ['continue', 'not_critical'],
            true
        )
            ? 'resolved'
            : 'acknowledged';

        $this->db
            ->where('id', $impact_id)
            ->update(
                'planning_assignment_impacts',
                [
                    'status' => $status,
                    'decision' => $decision,
                    'decision_notes' => trim($notes),
                    'id_users_decided_by' =>
                        (int) session('user_id'),
                    'resolved_datetime' =>
                        $status === 'resolved'
                            ? date('Y-m-d H:i:s')
                            : null,
                    'update_datetime' =>
                        date('Y-m-d H:i:s'),
                ]
            );
    }

    private function get_employee_impacts(
        array $assignment
    ): array {
        if (!$this->db->table_exists('employee_absences')) {
            return [];
        }

        $employee_ids = $this->relation_ids(
            'planning_assignment_employees',
            'id_planning_assignments',
            (int) $assignment['id'],
            'id_users'
        );

        if (!$employee_ids) {
            return [];
        }

        $start_column = $this->first_existing_field(
            'employee_absences',
            [
                'start_datetime',
                'start_date',
                'date_start',
            ]
        );

        $end_column = $this->first_existing_field(
            'employee_absences',
            [
                'end_datetime',
                'end_date',
                'date_end',
            ]
        );

        if (!$start_column || !$end_column) {
            return [];
        }

        $this->db
            ->select(
                "employee_absences.*,
                 users.first_name,
                 users.last_name"
            )
            ->from('employee_absences')
            ->join(
                'users',
                'users.id =
                    employee_absences.id_users_employee',
                'inner'
            )
            ->where_in(
                'employee_absences.id_users_employee',
                $employee_ids
            )
            ->where(
                "employee_absences.{$start_column} <=",
                $assignment['end_datetime']
            )
            ->where(
                "employee_absences.{$end_column} >=",
                $assignment['start_datetime']
            );

        if (
            $this->db->field_exists(
                'status',
                'employee_absences'
            )
        ) {
            $this->db->where_not_in(
                'employee_absences.status',
                ['cancelled', 'rejected']
            );
        }

        $absences = $this->db
            ->get()
            ->result_array();

        $impacts = [];
        $total_employees = count($employee_ids);

        foreach ($absences as $absence) {
            $name = trim(
                ($absence['first_name'] ?? '') .
                ' ' .
                ($absence['last_name'] ?? '')
            );

            $severity =
                $total_employees <= 1
                    ? 'critical'
                    : 'warning';

            $impacts[] = [
                'impact_type' =>
                    'employee_unavailable',
                'source_type' =>
                    'employee_absence',
                'source_id' =>
                    (int) $absence['id'],
                'severity' => $severity,
                'message' =>
                    ($name ?: 'Mitarbeiter') .
                    ' ist im Einsatzzeitraum nicht verfügbar.',
            ];
        }

        return $impacts;
    }

    private function get_employment_impacts(
        array $assignment
    ): array {
        // Historische Einsätze bleiben unangetastet.
        if (
            strtotime($assignment['end_datetime']) <
            time()
        ) {
            return [];
        }

        $employee_ids = $this->relation_ids(
            'planning_assignment_employees',
            'id_planning_assignments',
            (int) $assignment['id'],
            'id_users'
        );

        if (!$employee_ids) {
            return [];
        }

        $employees = $this->db
            ->select(
                'id,
                 first_name,
                 last_name,
                 employment_start,
                 employment_end'
            )
            ->where_in('id', $employee_ids)
            ->get('users')
            ->result_array();

        $impacts = [];

        foreach ($employees as $employee) {
            $name = trim(
                ($employee['first_name'] ?? '') .
                ' ' .
                ($employee['last_name'] ?? '')
            );

            if (
                !empty($employee['employment_end'])
            ) {
                $employment_end =
                    $employee['employment_end'] .
                    ' 23:59:59';

                if (
                    strtotime(
                        $assignment['start_datetime']
                    ) >
                    strtotime($employment_end)
                ) {
                    $impacts[] = [
                        'impact_type' =>
                            'employee_employment_ended',
                        'source_type' =>
                            'employee_employment',
                        'source_id' =>
                            (int) $employee['id'],
                        'severity' => 'critical',
                        'message' =>
                            ($name ?: 'Mitarbeiter') .
                            ' ist nach dem hinterlegten ' .
                            'Austrittsdatum (' .
                            date(
                                'd.m.Y',
                                strtotime(
                                    $employee[
                                        'employment_end'
                                    ]
                                )
                            ) .
                            ') noch für diesen Einsatz eingeplant.',
                    ];
                }
            }

            if (
                !empty($employee['employment_start']) &&
                strtotime(
                    $assignment['end_datetime']
                ) <
                strtotime(
                    $employee['employment_start'] .
                    ' 00:00:00'
                )
            ) {
                $impacts[] = [
                    'impact_type' =>
                        'employee_not_started',
                    'source_type' =>
                        'employee_employment_start',
                    'source_id' =>
                        (int) $employee['id'],
                    'severity' => 'critical',
                    'message' =>
                        ($name ?: 'Mitarbeiter') .
                        ' ist vor dem hinterlegten ' .
                        'Eintrittsdatum für diesen ' .
                        'Einsatz eingeplant.',
                ];
            }
        }

        return $impacts;
    }

    private function get_resource_impacts(
        array $assignment
    ): array {
        if (
            !$this->db->table_exists(
                'planning_resource_unavailabilities'
            )
        ) {
            return [];
        }

        $resource_ids = $this->relation_ids(
            'planning_assignment_resources',
            'id_planning_assignments',
            (int) $assignment['id'],
            'id_planning_resources'
        );

        if (!$resource_ids) {
            return [];
        }

        $unavailabilities = $this->db
            ->select(
                'rpu.*,
                 pr.name AS resource_name'
            )
            ->from(
                'planning_resource_unavailabilities AS rpu'
            )
            ->join(
                'planning_resources AS pr',
                'pr.id = rpu.id_planning_resources',
                'inner'
            )
            ->where_in(
                'rpu.id_planning_resources',
                $resource_ids
            )
            ->where(
                'rpu.status',
                'active'
            )
            ->where(
                'rpu.start_datetime <',
                $assignment['end_datetime']
            )
            ->group_start()
            ->where(
                'rpu.end_datetime >',
                $assignment['start_datetime']
            )
            ->or_where(
                'rpu.end_datetime IS NULL',
                null,
                false
            )
            ->group_end()
            ->get()
            ->result_array();

        $impacts = [];

        foreach ($unavailabilities as $unavailability) {
            $type = match (
                $unavailability['type']
            ) {
                'defect' => 'Defekt',
                'maintenance' => 'Wartung',
                'blocked' => 'Gesperrt',
                default => 'Nicht verfügbar',
            };

            $assignment_start = strtotime(
                $assignment['start_datetime']
            );

            $assignment_end = strtotime(
                $assignment['end_datetime']
            );

            $unavailability_start = strtotime(
                $unavailability['start_datetime']
            );

            $unavailability_end =
                !empty($unavailability['end_datetime'])
                    ? strtotime(
                        $unavailability['end_datetime']
                    )
                    : $assignment_end;

            $overlap_start = max(
                $assignment_start,
                $unavailability_start
            );

            $overlap_end = min(
                $assignment_end,
                $unavailability_end
            );

            $fully_affected =
                $overlap_start <= $assignment_start &&
                $overlap_end >= $assignment_end;

            $resource_name =
                $unavailability['resource_name'] ??
                'Ressource';

            if ($fully_affected) {
                $message =
                    $resource_name .
                    ' ist wegen ' .
                    $type .
                    ' für den gesamten Einsatzzeitraum ' .
                    'nicht verfügbar (' .
                    date(
                        'd.m.Y H:i',
                        $assignment_start
                    ) .
                    ' bis ' .
                    date(
                        'd.m.Y H:i',
                        $assignment_end
                    ) .
                    ').';
            } else {
                $message =
                    $resource_name .
                    ' ist wegen ' .
                    $type .
                    ' teilweise nicht verfügbar: ' .
                    date(
                        'd.m.Y H:i',
                        $overlap_start
                    ) .
                    ' bis ' .
                    date(
                        'd.m.Y H:i',
                        $overlap_end
                    ) .
                    '. Der Einsatz ist nur teilweise betroffen.';
            }

            $impacts[] = [
                'impact_type' =>
                    'resource_unavailable',
                'source_type' =>
                    'resource_unavailability',
                'source_id' =>
                    (int) $unavailability['id'],
                'severity' =>
                    $fully_affected
                        ? 'critical'
                        : 'warning',
                'message' => $message,
            ];
        }

        return $impacts;
    }

    private function upsert_impact(
        int $assignment_id,
        array $impact
    ): bool {
        $existing = $this->db
            ->get_where(
                'planning_assignment_impacts',
                [
                    'id_planning_assignments' =>
                        $assignment_id,
                    'source_type' =>
                        $impact['source_type'],
                    'source_id' =>
                        $impact['source_id'],
                ]
            )
            ->row_array();

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $was_resolved =
                $existing['status'] === 'resolved';

            $this->db
                ->where('id', (int) $existing['id'])
                ->update(
                    'planning_assignment_impacts',
                    [
                        'impact_type' =>
                            $impact['impact_type'],
                        'severity' =>
                            $impact['severity'],
                        'status' =>
                            $was_resolved
                                ? 'open'
                                : $existing['status'],
                        'message' =>
                            $impact['message'],
                        'resolved_datetime' =>
                            $was_resolved
                                ? null
                                : $existing[
                                    'resolved_datetime'
                                ],
                        'update_datetime' => $now,
                    ]
                );

            return $was_resolved;
        }

        $this->db->insert(
            'planning_assignment_impacts',
            [
                'id_planning_assignments' =>
                    $assignment_id,
                'impact_type' =>
                    $impact['impact_type'],
                'source_type' =>
                    $impact['source_type'],
                'source_id' =>
                    $impact['source_id'],
                'severity' =>
                    $impact['severity'],
                'status' => 'open',
                'message' =>
                    $impact['message'],
                'decision' => null,
                'decision_notes' => null,
                'id_users_decided_by' => null,
                'detected_datetime' => $now,
                'resolved_datetime' => null,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]
        );

        return true;
    }

    private function resolve_missing_impacts(
        int $assignment_id,
        array $active_sources
    ): int {
        $existing = $this->db
            ->where(
                'id_planning_assignments',
                $assignment_id
            )
            ->where_in(
                'status',
                ['open', 'acknowledged']
            )
            ->get('planning_assignment_impacts')
            ->result_array();

        $resolved = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($existing as $impact) {
            $key =
                $impact['source_type'] . ':' .
                $impact['source_id'];

            if (in_array($key, $active_sources, true)) {
                continue;
            }

            $this->db
                ->where('id', (int) $impact['id'])
                ->update(
                    'planning_assignment_impacts',
                    [
                        'status' => 'resolved',
                        'resolved_datetime' => $now,
                        'update_datetime' => $now,
                    ]
                );

            $resolved++;
        }

        return $resolved;
    }

    private function relation_ids(
        string $table,
        string $reference_column,
        int $reference_id,
        string $target_column
    ): array {
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $rows = $this->db
            ->select($target_column)
            ->where($reference_column, $reference_id)
            ->get($table)
            ->result_array();

        return array_values(
            array_unique(
                array_map(
                    static fn(array $row): int =>
                        (int) $row[$target_column],
                    $rows
                )
            )
        );
    }

    private function first_existing_field(
        string $table,
        array $fields
    ): ?string {
        foreach ($fields as $field) {
            if ($this->db->field_exists($field, $table)) {
                return $field;
            }
        }

        return null;
    }
}
