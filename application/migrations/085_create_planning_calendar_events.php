<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_calendar_events extends EA_Migration
{
    public function up(): void
    {
        $this->createEventsTable();
        $this->createRelationsTable();
        $this->addForeignKeys();

        $this->backfillProjectAssignments();
        $this->backfillAppointments();
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'planning_calendar_event_calendars',
            true
        );

        $this->dbforge->drop_table(
            'planning_calendar_events',
            true
        );
    }

    private function createEventsTable(): void
    {
        if (
            $this->db->table_exists(
                'planning_calendar_events'
            )
        ) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ],

            'reference_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],

            'create_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],

            'update_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('event_type');
        $this->dbforge->add_key('reference_id');

        $this->dbforge->create_table(
            'planning_calendar_events',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix(
            'planning_calendar_events'
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD UNIQUE KEY `calendar_event_reference_unique`
             (`event_type`, `reference_id`)"
        );
    }

    private function createRelationsTable(): void
    {
        if (
            $this->db->table_exists(
                'planning_calendar_event_calendars'
            )
        ) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'id_planning_calendar_events' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],

            'id_planning_calendars' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],

            'create_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key(
            'id_planning_calendar_events'
        );
        $this->dbforge->add_key(
            'id_planning_calendars'
        );

        $this->dbforge->create_table(
            'planning_calendar_event_calendars',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix(
            'planning_calendar_event_calendars'
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD UNIQUE KEY `calendar_event_calendar_unique`
             (
                 `id_planning_calendar_events`,
                 `id_planning_calendars`
             )"
        );
    }

    private function addForeignKeys(): void
    {
        $relations = $this->db->dbprefix(
            'planning_calendar_event_calendars'
        );

        $events = $this->db->dbprefix(
            'planning_calendar_events'
        );

        $calendars = $this->db->dbprefix(
            'planning_calendars'
        );

        $eventConstraint =
            $this->db->dbprefix .
            'calendar_event_calendars_event';

        if (!$this->constraintExists($eventConstraint)) {
            $this->db->query(
                "ALTER TABLE `{$relations}`
                 ADD CONSTRAINT `{$eventConstraint}`
                 FOREIGN KEY (`id_planning_calendar_events`)
                 REFERENCES `{$events}` (`id`)
                 ON DELETE CASCADE
                 ON UPDATE CASCADE"
            );
        }

        $calendarConstraint =
            $this->db->dbprefix .
            'calendar_event_calendars_calendar';

        if (
            !$this->constraintExists(
                $calendarConstraint
            )
        ) {
            $this->db->query(
                "ALTER TABLE `{$relations}`
                 ADD CONSTRAINT `{$calendarConstraint}`
                 FOREIGN KEY (`id_planning_calendars`)
                 REFERENCES `{$calendars}` (`id`)
                 ON DELETE CASCADE
                 ON UPDATE CASCADE"
            );
        }
    }

    private function backfillProjectAssignments(): void
    {
        if (
            !$this->db->table_exists(
                'planning_assignments'
            )
        ) {
            return;
        }

        $assignments = $this->db
            ->select('id, id_planning_projects')
            ->get('planning_assignments')
            ->result_array();

        foreach ($assignments as $assignment) {
            $assignmentId = (int) $assignment['id'];

            $eventId = $this->ensureEvent(
                'project_assignment',
                $assignmentId
            );

            $this->attachEntityCalendar(
                $eventId,
                'project',
                (int) $assignment[
                    'id_planning_projects'
                ]
            );

            $employees = $this->relationIds(
                'planning_assignment_employees',
                'id_planning_assignments',
                $assignmentId,
                'id_users'
            );

            foreach ($employees as $employeeId) {
                $this->attachEntityCalendar(
                    $eventId,
                    'employee',
                    $employeeId
                );
            }

            $teams = $this->relationIds(
                'planning_assignment_teams',
                'id_planning_assignments',
                $assignmentId,
                'id_employee_teams'
            );

            foreach ($teams as $teamId) {
                $this->attachEntityCalendar(
                    $eventId,
                    'team',
                    $teamId
                );
            }

            $resources = $this->relationIds(
                'planning_assignment_resources',
                'id_planning_assignments',
                $assignmentId,
                'id_planning_resources'
            );

            foreach ($resources as $resourceId) {
                $this->attachEntityCalendar(
                    $eventId,
                    'resource',
                    $resourceId
                );
            }
        }
    }

    private function backfillAppointments(): void
    {
        if (!$this->db->table_exists('appointments')) {
            return;
        }

        $appointments = $this->db
            ->select(
                'id,
                 id_users_provider,
                 is_unavailability'
            )
            ->get('appointments')
            ->result_array();

        foreach ($appointments as $appointment) {
            $appointmentId = (int) $appointment['id'];

            $eventType =
                (int) $appointment['is_unavailability'] === 1
                    ? 'unavailability'
                    : 'appointment';

            $eventId = $this->ensureEvent(
                $eventType,
                $appointmentId
            );

            if (!empty($appointment['id_users_provider'])) {
                $this->attachEntityCalendar(
                    $eventId,
                    'employee',
                    (int) $appointment[
                        'id_users_provider'
                    ]
                );
            }

            if (
                $eventType === 'appointment' &&
                $this->db->table_exists(
                    'appointment_resources'
                )
            ) {
                $resources = $this->relationIds(
                    'appointment_resources',
                    'id_appointments',
                    $appointmentId,
                    'id_planning_resources'
                );

                foreach ($resources as $resourceId) {
                    $this->attachEntityCalendar(
                        $eventId,
                        'resource',
                        $resourceId
                    );
                }
            }
        }
    }

    private function ensureEvent(
        string $eventType,
        int $referenceId
    ): int {
        $existing = $this->db
            ->select('id')
            ->get_where(
                'planning_calendar_events',
                [
                    'event_type' => $eventType,
                    'reference_id' => $referenceId,
                ]
            )
            ->row_array();

        if ($existing) {
            return (int) $existing['id'];
        }

        $now = date('Y-m-d H:i:s');

        $this->db->insert(
            'planning_calendar_events',
            [
                'event_type' => $eventType,
                'reference_id' => $referenceId,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]
        );

        return (int) $this->db->insert_id();
    }

    private function attachEntityCalendar(
        int $eventId,
        string $entityType,
        int $entityId
    ): void {
        $calendar = $this->db
            ->select('id')
            ->get_where(
                'planning_calendars',
                [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'is_active' => 1,
                ]
            )
            ->row_array();

        if (!$calendar) {
            return;
        }

        $calendarId = (int) $calendar['id'];

        $exists = $this->db
            ->where(
                'id_planning_calendar_events',
                $eventId
            )
            ->where(
                'id_planning_calendars',
                $calendarId
            )
            ->count_all_results(
                'planning_calendar_event_calendars'
            );

        if ($exists) {
            return;
        }

        $this->db->insert(
            'planning_calendar_event_calendars',
            [
                'id_planning_calendar_events' =>
                    $eventId,
                'id_planning_calendars' =>
                    $calendarId,
                'create_datetime' =>
                    date('Y-m-d H:i:s'),
            ]
        );
    }

    private function relationIds(
        string $table,
        string $referenceColumn,
        int $referenceId,
        string $targetColumn
    ): array {
        if (!$this->db->table_exists($table)) {
            return [];
        }

        $rows = $this->db
            ->select($targetColumn)
            ->where($referenceColumn, $referenceId)
            ->get($table)
            ->result_array();

        return array_values(
            array_unique(
                array_map(
                    static fn(array $row): int =>
                        (int) $row[$targetColumn],
                    $rows
                )
            )
        );
    }

    private function constraintExists(
        string $constraint
    ): bool {
        return (bool) $this->db
            ->query(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME = ?',
                [$constraint]
            )
            ->row_array();
    }
}
