<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_assignments_model extends EA_Model
{
    private const ALLOWED_STATUSES = [
        'planned',
        'confirmed',
        'in_progress',
        'completed',
        'cancelled',
    ];

    public function get_by_project(int $project_id): array
    {
        $assignments = $this->db
            ->select(
                'planning_assignments.*,
                 planning_projects.name AS project_name,
                 planning_project_phases.name AS phase_name'
            )
            ->from('planning_assignments')
            ->join(
                'planning_projects',
                'planning_projects.id =
                    planning_assignments.id_planning_projects',
                'inner'
            )
            ->join(
                'planning_project_phases',
                'planning_project_phases.id =
                    planning_assignments.id_planning_project_phases',
                'left'
            )
            ->where(
                'planning_assignments.id_planning_projects',
                $project_id
            )
            ->order_by(
                'planning_assignments.start_datetime',
                'ASC'
            )
            ->order_by(
                'planning_assignments.id',
                'ASC'
            )
            ->get()
            ->result_array();

        foreach ($assignments as &$assignment) {
            $this->append_relations($assignment);
        }

        unset($assignment);

        return $assignments;
    }

    public function find(int $assignment_id): ?array
    {
        $assignment = $this->db
            ->select(
                'planning_assignments.*,
                 planning_projects.name AS project_name,
                 planning_project_phases.name AS phase_name'
            )
            ->from('planning_assignments')
            ->join(
                'planning_projects',
                'planning_projects.id =
                    planning_assignments.id_planning_projects',
                'inner'
            )
            ->join(
                'planning_project_phases',
                'planning_project_phases.id =
                    planning_assignments.id_planning_project_phases',
                'left'
            )
            ->where(
                'planning_assignments.id',
                $assignment_id
            )
            ->get()
            ->row_array();

        if (!$assignment) {
            return null;
        }

        $this->append_relations($assignment);

        return $assignment;
    }

    public function save(array $assignment): int
    {
        $this->validate_assignment($assignment);

        $assignment_id = !empty($assignment['id'])
            ? (int) $assignment['id']
            : null;

        $project_id = (int) $assignment[
            'id_planning_projects'
        ];

        $phase_id = !empty(
            $assignment['id_planning_project_phases']
        )
            ? (int) $assignment[
                'id_planning_project_phases'
            ]
            : null;

        $project = $this->db
            ->get_where(
                'planning_projects',
                ['id' => $project_id]
            )
            ->row_array();

        if (!$project) {
            throw new RuntimeException(
                'Das zugehörige Projekt wurde nicht gefunden.'
            );
        }

        if ($phase_id) {
            $phase = $this->db
                ->get_where(
                    'planning_project_phases',
                    ['id' => $phase_id]
                )
                ->row_array();

            if (
                !$phase ||
                (int) $phase['id_planning_projects'] !==
                    $project_id
            ) {
                throw new InvalidArgumentException(
                    'Der gewählte Baustein gehört nicht zu diesem Projekt.'
                );
            }
        }

        $employees = $this->normalize_ids(
            $assignment['employees'] ?? []
        );

        $teams = $this->normalize_ids(
            $assignment['teams'] ?? []
        );

        $resources = $this->normalize_ids(
            $assignment['resources'] ?? []
        );

        $this->validate_employee_conflicts(
            $employees,
            $assignment['start_datetime'],
            $assignment['end_datetime'],
            $assignment_id
        );

        $this->validate_resource_conflicts(
            $resources,
            $assignment['start_datetime'],
            $assignment['end_datetime'],
            $assignment_id
        );

        $now = date('Y-m-d H:i:s');

        $record = [
            'id_planning_projects' => $project_id,
            'id_planning_project_phases' => $phase_id,
            'title' => trim(
                (string) $assignment['title']
            ),
            'start_datetime' =>
                $assignment['start_datetime'],
            'end_datetime' =>
                $assignment['end_datetime'],
            'location' => trim(
                (string) (
                    $assignment['location'] ?? ''
                )
            ),
            'priority' => min(
                100,
                max(
                    0,
                    (int) (
                        $assignment['priority'] ?? 70
                    )
                )
            ),
            'status' =>
                $assignment['status'] ?? 'planned',
            'notes' => trim(
                (string) ($assignment['notes'] ?? '')
            ),
            'color' =>
                $assignment['color'] ?? '#f28c28',
            'update_datetime' => $now,
        ];

        $this->db->trans_begin();

        try {
            if ($assignment_id) {
                $existing = $this->find($assignment_id);

                if (
                    !$existing ||
                    (int) $existing[
                        'id_planning_projects'
                    ] !== $project_id
                ) {
                    throw new RuntimeException(
                        'Projekteinsatz nicht gefunden.'
                    );
                }

                $this->db
                    ->where('id', $assignment_id)
                    ->update(
                        'planning_assignments',
                        $record
                    );
            } else {
                $record['create_datetime'] = $now;

                $this->db->insert(
                    'planning_assignments',
                    $record
                );

                $assignment_id =
                    (int) $this->db->insert_id();
            }

            $this->save_relation(
                'planning_assignment_employees',
                'id_users',
                $assignment_id,
                $employees,
                $now
            );

            $this->save_relation(
                'planning_assignment_teams',
                'id_employee_teams',
                $assignment_id,
                $teams,
                $now
            );

            $this->save_relation(
                'planning_assignment_resources',
                'id_planning_resources',
                $assignment_id,
                $resources,
                $now
            );

            $this->sync_calendar_event(
                $assignment_id,
                $project_id,
                $employees,
                $teams,
                $resources,
                $now
            );

            if ($this->db->trans_status() === false) {
                throw new RuntimeException(
                    'Der Einsatz konnte nicht vollständig gespeichert werden.'
                );
            }

            $this->db->trans_commit();

            return $assignment_id;
        } catch (Throwable $e) {
            $this->db->trans_rollback();

            throw $e;
        }
    }

    public function delete(int $assignment_id): void
    {
        if (!$this->find($assignment_id)) {
            throw new RuntimeException(
                'Projekteinsatz nicht gefunden.'
            );
        }

        $this->db->trans_begin();

        try {
            if (
                $this->db->table_exists(
                    'planning_calendar_events'
                )
            ) {
                $this->db->delete(
                    'planning_calendar_events',
                    [
                        'event_type' =>
                            'project_assignment',
                        'reference_id' =>
                            $assignment_id,
                    ]
                );
            }

            $this->db->delete(
                'planning_assignments',
                ['id' => $assignment_id]
            );

            if ($this->db->trans_status() === false) {
                throw new RuntimeException(
                    'Der Projekteinsatz konnte nicht ' .
                    'vollständig gelöscht werden.'
                );
            }

            $this->db->trans_commit();
        } catch (Throwable $e) {
            $this->db->trans_rollback();

            throw $e;
        }
    }

    private function sync_calendar_event(
        int $assignment_id,
        int $project_id,
        array $employee_ids,
        array $team_ids,
        array $resource_ids,
        string $now
    ): void {
        if (
            !$this->db->table_exists(
                'planning_calendar_events'
            ) ||
            !$this->db->table_exists(
                'planning_calendar_event_calendars'
            ) ||
            !$this->db->table_exists(
                'planning_calendars'
            )
        ) {
            return;
        }

        $event = $this->db
            ->select('id')
            ->get_where(
                'planning_calendar_events',
                [
                    'event_type' =>
                        'project_assignment',
                    'reference_id' =>
                        $assignment_id,
                ]
            )
            ->row_array();

        if ($event) {
            $event_id = (int) $event['id'];

            $this->db
                ->where('id', $event_id)
                ->update(
                    'planning_calendar_events',
                    [
                        'update_datetime' => $now,
                    ]
                );
        } else {
            $this->db->insert(
                'planning_calendar_events',
                [
                    'event_type' =>
                        'project_assignment',
                    'reference_id' =>
                        $assignment_id,
                    'create_datetime' => $now,
                    'update_datetime' => $now,
                ]
            );

            $event_id =
                (int) $this->db->insert_id();
        }

        /*
         * Die Zuordnungen werden vollständig neu aufgebaut.
         * Dadurch verschwinden entfernte Mitarbeiter, Teams und
         * Ressourcen ebenfalls aus ihren Kalendern.
         */
        $this->db->delete(
            'planning_calendar_event_calendars',
            [
                'id_planning_calendar_events' =>
                    $event_id,
            ]
        );

        $this->attach_calendar(
            $event_id,
            'project',
            $project_id,
            $now
        );

        foreach ($employee_ids as $employee_id) {
            $this->attach_calendar(
                $event_id,
                'employee',
                (int) $employee_id,
                $now
            );
        }

        foreach ($team_ids as $team_id) {
            $this->attach_calendar(
                $event_id,
                'team',
                (int) $team_id,
                $now
            );
        }

        foreach ($resource_ids as $resource_id) {
            $this->attach_calendar(
                $event_id,
                'resource',
                (int) $resource_id,
                $now
            );
        }
    }

    private function attach_calendar(
        int $event_id,
        string $entity_type,
        int $entity_id,
        string $now
    ): void {
        if ($entity_id < 1) {
            return;
        }

        $calendar = $this->db
            ->select('id')
            ->from('planning_calendars')
            ->where('entity_type', $entity_type)
            ->where('entity_id', $entity_id)
            ->where('is_active', 1)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$calendar) {
            return;
        }

        $this->db->insert(
            'planning_calendar_event_calendars',
            [
                'id_planning_calendar_events' =>
                    $event_id,
                'id_planning_calendars' =>
                    (int) $calendar['id'],
                'create_datetime' => $now,
            ]
        );
    }

    private function validate_assignment(
        array $assignment
    ): void {
        if (
            empty($assignment['id_planning_projects']) ||
            trim(
                (string) ($assignment['title'] ?? '')
            ) === '' ||
            empty($assignment['start_datetime']) ||
            empty($assignment['end_datetime'])
        ) {
            throw new InvalidArgumentException(
                'Projekt, Titel, Beginn und Ende sind erforderlich.'
            );
        }

        if (
            $assignment['end_datetime'] <=
            $assignment['start_datetime']
        ) {
            throw new InvalidArgumentException(
                'Das Einsatzende muss nach dem Beginn liegen.'
            );
        }

        $status =
            $assignment['status'] ?? 'planned';

        if (
            !in_array(
                $status,
                self::ALLOWED_STATUSES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Der Einsatzstatus ist ungültig.'
            );
        }

        $color = (string) (
            $assignment['color'] ?? '#f28c28'
        );

        if (!validate_color($color)) {
            throw new InvalidArgumentException(
                'Die Einsatzfarbe ist ungültig.'
            );
        }
    }

    private function validate_employee_conflicts(
        array $employee_ids,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_assignment_id
    ): void {
        foreach ($employee_ids as $employee_id) {
            $this->db
                ->select(
                    'planning_assignments.title,
                     planning_assignments.start_datetime,
                     planning_assignments.end_datetime'
                )
                ->from(
                    'planning_assignment_employees'
                )
                ->join(
                    'planning_assignments',
                    'planning_assignments.id =
                        planning_assignment_employees.id_planning_assignments',
                    'inner'
                )
                ->where(
                    'planning_assignment_employees.id_users',
                    $employee_id
                )
                ->where(
                    'planning_assignments.status !=',
                    'cancelled'
                )
                ->where(
                    'planning_assignments.start_datetime <',
                    $end_datetime
                )
                ->where(
                    'planning_assignments.end_datetime >',
                    $start_datetime
                );

            if ($exclude_assignment_id) {
                $this->db->where(
                    'planning_assignments.id !=',
                    $exclude_assignment_id
                );
            }

            $conflict = $this->db
                ->get()
                ->row_array();

            if (!$conflict) {
                continue;
            }

            $user = $this->db
                ->select('first_name, last_name')
                ->get_where(
                    'users',
                    ['id' => $employee_id]
                )
                ->row_array();

            $name = trim(
                ($user['first_name'] ?? '') .
                ' ' .
                ($user['last_name'] ?? '')
            );

            throw new RuntimeException(
                'Mitarbeiterkonflikt: ' .
                ($name ?: 'Mitarbeiter') .
                ' ist bereits im Einsatz „' .
                $conflict['title'] .
                '“ von ' .
                date(
                    'd.m.Y H:i',
                    strtotime(
                        $conflict['start_datetime']
                    )
                ) .
                ' bis ' .
                date(
                    'd.m.Y H:i',
                    strtotime(
                        $conflict['end_datetime']
                    )
                ) .
                ' eingeplant.'
            );
        }
    }

    private function validate_resource_conflicts(
        array $resource_ids,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_assignment_id
    ): void {
        foreach ($resource_ids as $resource_id) {
            $this->db
                ->select(
                    'planning_assignments.title,
                     planning_assignments.start_datetime,
                     planning_assignments.end_datetime'
                )
                ->from(
                    'planning_assignment_resources'
                )
                ->join(
                    'planning_assignments',
                    'planning_assignments.id =
                        planning_assignment_resources.id_planning_assignments',
                    'inner'
                )
                ->where(
                    'planning_assignment_resources.id_planning_resources',
                    $resource_id
                )
                ->where(
                    'planning_assignments.status !=',
                    'cancelled'
                )
                ->where(
                    'planning_assignments.start_datetime <',
                    $end_datetime
                )
                ->where(
                    'planning_assignments.end_datetime >',
                    $start_datetime
                );

            if ($exclude_assignment_id) {
                $this->db->where(
                    'planning_assignments.id !=',
                    $exclude_assignment_id
                );
            }

            $conflict = $this->db
                ->get()
                ->row_array();

            if (!$conflict) {
                continue;
            }

            $resource = $this->db
                ->select('name')
                ->get_where(
                    'planning_resources',
                    ['id' => $resource_id]
                )
                ->row_array();

            throw new RuntimeException(
                'Ressourcenkonflikt: ' .
                ($resource['name'] ?? 'Ressource') .
                ' ist bereits im Einsatz „' .
                $conflict['title'] .
                '“ von ' .
                date(
                    'd.m.Y H:i',
                    strtotime(
                        $conflict['start_datetime']
                    )
                ) .
                ' bis ' .
                date(
                    'd.m.Y H:i',
                    strtotime(
                        $conflict['end_datetime']
                    )
                ) .
                ' eingeplant.'
            );
        }
    }

    private function append_relations(
        array &$assignment
    ): void {
        $assignment_id =
            (int) $assignment['id'];

        $assignment['employees'] =
            $this->get_relation_ids(
                'planning_assignment_employees',
                'id_users',
                $assignment_id
            );

        $assignment['teams'] =
            $this->get_relation_ids(
                'planning_assignment_teams',
                'id_employee_teams',
                $assignment_id
            );

        $assignment['resources'] =
            $this->get_relation_ids(
                'planning_assignment_resources',
                'id_planning_resources',
                $assignment_id
            );

        $assignment['employee_count'] = count(
            $assignment['employees']
        );

        $assignment['team_count'] = count(
            $assignment['teams']
        );

        $assignment['resource_count'] = count(
            $assignment['resources']
        );
    }

    private function get_relation_ids(
        string $table,
        string $target_column,
        int $assignment_id
    ): array {
        $rows = $this->db
            ->select($target_column)
            ->where(
                'id_planning_assignments',
                $assignment_id
            )
            ->get($table)
            ->result_array();

        return array_map(
            static fn(array $row): int =>
                (int) $row[$target_column],
            $rows
        );
    }

    private function save_relation(
        string $table,
        string $target_column,
        int $assignment_id,
        array $target_ids,
        string $now
    ): void {
        $this->db->delete(
            $table,
            [
                'id_planning_assignments' =>
                    $assignment_id,
            ]
        );

        foreach ($target_ids as $target_id) {
            $this->db->insert($table, [
                'id_planning_assignments' =>
                    $assignment_id,
                $target_column => $target_id,
                'create_datetime' => $now,
            ]);
        }
    }

    private function normalize_ids(
        array $ids
    ): array {
        return array_values(
            array_unique(
                array_filter(
                    array_map('intval', $ids),
                    static fn(int $id): bool =>
                        $id > 0
                )
            )
        );
    }
}
