<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_assignment_relations extends EA_Migration
{
    public function up(): void
    {
        $this->createEmployees();
        $this->createTeams();
        $this->createResources();

        $this->addForeignKey(
            'planning_assignment_employees',
            'assignment_employees_assignment',
            'id_planning_assignments',
            'planning_assignments',
            'id'
        );

        $this->addForeignKey(
            'planning_assignment_employees',
            'assignment_employees_user',
            'id_users',
            'users',
            'id'
        );

        $this->addForeignKey(
            'planning_assignment_teams',
            'assignment_teams_assignment',
            'id_planning_assignments',
            'planning_assignments',
            'id'
        );

        $this->addForeignKey(
            'planning_assignment_teams',
            'assignment_teams_team',
            'id_employee_teams',
            'employee_teams',
            'id'
        );

        $this->addForeignKey(
            'planning_assignment_resources',
            'assignment_resources_assignment',
            'id_planning_assignments',
            'planning_assignments',
            'id'
        );

        $this->addForeignKey(
            'planning_assignment_resources',
            'assignment_resources_resource',
            'id_planning_resources',
            'planning_resources',
            'id'
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'planning_assignment_resources',
            true
        );

        $this->dbforge->drop_table(
            'planning_assignment_teams',
            true
        );

        $this->dbforge->drop_table(
            'planning_assignment_employees',
            true
        );
    }

    private function createEmployees(): void
    {
        if (
            $this->db->table_exists(
                'planning_assignment_employees'
            )
        ) {
            return;
        }

        $this->createRelationTable(
            'planning_assignment_employees',
            'id_users',
            false
        );
    }

    private function createTeams(): void
    {
        if (
            $this->db->table_exists(
                'planning_assignment_teams'
            )
        ) {
            return;
        }

        $this->createRelationTable(
            'planning_assignment_teams',
            'id_employee_teams',
            true
        );
    }

    private function createResources(): void
    {
        if (
            $this->db->table_exists(
                'planning_assignment_resources'
            )
        ) {
            return;
        }

        $this->createRelationTable(
            'planning_assignment_resources',
            'id_planning_resources',
            true
        );
    }

    private function createRelationTable(
        string $table,
        string $targetColumn,
        bool $unsignedTarget
    ): void {
        $target = [
            'type' => 'INT',
            'constraint' => 11,
            'null' => false,
        ];

        if ($unsignedTarget) {
            $target['unsigned'] = true;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'id_planning_assignments' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],

            $targetColumn => $target,

            'create_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('id_planning_assignments');
        $this->dbforge->add_key($targetColumn);

        $this->dbforge->create_table(
            $table,
            true,
            ['engine' => 'InnoDB']
        );

        $tableName = $this->db->dbprefix($table);

        $this->db->query(
            "ALTER TABLE `{$tableName}`
             ADD UNIQUE KEY `assignment_target_unique`
             (`id_planning_assignments`, `{$targetColumn}`)"
        );
    }

    private function addForeignKey(
        string $table,
        string $suffix,
        string $column,
        string $referenceTable,
        string $referenceColumn
    ): void {
        $constraint = $this->db->dbprefix . $suffix;
        $tableName = $this->db->dbprefix($table);
        $referenceName =
            $this->db->dbprefix($referenceTable);

        $existing = $this->db
            ->query(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME = ?',
                [$constraint]
            )
            ->row_array();

        if ($existing) {
            return;
        }

        $this->db->query(
            "ALTER TABLE `{$tableName}`
             ADD CONSTRAINT `{$constraint}`
             FOREIGN KEY (`{$column}`)
             REFERENCES `{$referenceName}` (`{$referenceColumn}`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );
    }
}
