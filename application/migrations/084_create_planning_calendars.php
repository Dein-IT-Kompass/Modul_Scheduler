<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_calendars extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('planning_calendars')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'entity_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => false,
                ],
                'entity_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 256,
                    'null' => false,
                ],
                'color' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => '#7cbae8',
                    'null' => false,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
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
            $this->dbforge->add_key('entity_type');
            $this->dbforge->add_key('entity_id');
            $this->dbforge->add_key('is_active');

            $this->dbforge->create_table(
                'planning_calendars',
                true,
                ['engine' => 'InnoDB']
            );

            $table = $this->db->dbprefix(
                'planning_calendars'
            );

            $this->db->query(
                "ALTER TABLE `{$table}`
                 ADD UNIQUE KEY `planning_calendar_entity_unique`
                 (`entity_type`, `entity_id`)"
            );
        }

        $this->createProjectCalendars();
        $this->createEmployeeCalendars();
        $this->createTeamCalendars();
        $this->createResourceCalendars();
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'planning_calendars',
            true
        );
    }

    private function insertCalendar(
        string $entityType,
        int $entityId,
        string $name,
        string $color
    ): void {
        $exists = $this->db
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->count_all_results('planning_calendars');

        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->insert(
            'planning_calendars',
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'name' => $name,
                'color' => $color,
                'is_active' => 1,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]
        );
    }

    private function createProjectCalendars(): void
    {
        $projects = $this->db
            ->select('id, name, color, status')
            ->get('planning_projects')
            ->result_array();

        foreach ($projects as $project) {
            if ($project['status'] === 'cancelled') {
                continue;
            }

            $this->insertCalendar(
                'project',
                (int) $project['id'],
                $project['name'],
                $project['color'] ?: '#f28c28'
            );
        }
    }

    private function createEmployeeCalendars(): void
    {
        $employees = $this->db
            ->select(
                'users.id,
                 users.first_name,
                 users.last_name'
            )
            ->from('users')
            ->join(
                'roles',
                'roles.id = users.id_roles',
                'inner'
            )
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->get()
            ->result_array();

        foreach ($employees as $employee) {
            $name = trim(
                $employee['first_name'] . ' ' .
                $employee['last_name']
            );

            $this->insertCalendar(
                'employee',
                (int) $employee['id'],
                $name,
                '#2878c8'
            );
        }
    }

    private function createTeamCalendars(): void
    {
        $teams = $this->db
            ->select('id, name')
            ->where('is_active', 1)
            ->get('employee_teams')
            ->result_array();

        foreach ($teams as $team) {
            $this->insertCalendar(
                'team',
                (int) $team['id'],
                $team['name'],
                '#2878c8'
            );
        }
    }

    private function createResourceCalendars(): void
    {
        $resources = $this->db
            ->select('id, name, color, is_active')
            ->get('planning_resources')
            ->result_array();

        foreach ($resources as $resource) {
            if (!(int) $resource['is_active']) {
                continue;
            }

            $this->insertCalendar(
                'resource',
                (int) $resource['id'],
                $resource['name'],
                $resource['color'] ?: '#2f9e68'
            );
        }
    }
}
