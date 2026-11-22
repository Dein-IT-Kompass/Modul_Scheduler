<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_projects extends EA_Migration
{
    public function up(): void
    {
        $this->createProjects();
        $this->createProjectEmployees();
        $this->createProjectTeams();
        $this->createProjectResources();
        $this->createProjectServices();

        $this->addForeignKey(
            'planning_project_employees',
            'planning_project_employees_project',
            'id_planning_projects',
            'planning_projects',
            'id',
        );

        $this->addForeignKey(
            'planning_project_employees',
            'planning_project_employees_user',
            'id_users',
            'users',
            'id',
        );

        $this->addForeignKey(
            'planning_project_teams',
            'planning_project_teams_project',
            'id_planning_projects',
            'planning_projects',
            'id',
        );

        $this->addForeignKey(
            'planning_project_teams',
            'planning_project_teams_team',
            'id_employee_teams',
            'employee_teams',
            'id',
        );

        $this->addForeignKey(
            'planning_project_resources',
            'planning_project_resources_project',
            'id_planning_projects',
            'planning_projects',
            'id',
        );

        $this->addForeignKey(
            'planning_project_resources',
            'planning_project_resources_resource',
            'id_planning_resources',
            'planning_resources',
            'id',
        );

        $this->addForeignKey(
            'planning_project_services',
            'planning_project_services_project',
            'id_planning_projects',
            'planning_projects',
            'id',
        );

        $this->addForeignKey(
            'planning_project_services',
            'planning_project_services_service',
            'id_services',
            'services',
            'id',
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table('planning_project_services', true);
        $this->dbforge->drop_table('planning_project_resources', true);
        $this->dbforge->drop_table('planning_project_teams', true);
        $this->dbforge->drop_table('planning_project_employees', true);
        $this->dbforge->drop_table('planning_projects', true);
    }

    private function createProjects(): void
    {
        if ($this->db->table_exists('planning_projects')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'location' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => true,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'planned',
                'null' => false,
            ],
            'id_users_project_lead' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'color' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => '#7cbae8',
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
        $this->dbforge->add_key('name');
        $this->dbforge->add_key('status');
        $this->dbforge->add_key('id_users_project_lead');

        $this->dbforge->create_table(
            'planning_projects',
            true,
            ['engine' => 'InnoDB'],
        );

        $this->addForeignKey(
            'planning_projects',
            'planning_projects_lead',
            'id_users_project_lead',
            'users',
            'id',
            'SET NULL',
        );
    }

    private function createProjectEmployees(): void
    {
        $this->createAssignmentTable(
            'planning_project_employees',
            'id_users',
        );
    }

    private function createProjectTeams(): void
    {
        $this->createAssignmentTable(
            'planning_project_teams',
            'id_employee_teams',
            true,
        );
    }

    private function createProjectResources(): void
    {
        $this->createAssignmentTable(
            'planning_project_resources',
            'id_planning_resources',
            true,
        );
    }

    private function createProjectServices(): void
    {
        $this->createAssignmentTable(
            'planning_project_services',
            'id_services',
        );
    }

    private function createAssignmentTable(
        string $table,
        string $targetColumn,
        bool $unsignedTarget = false
    ): void {
        if ($this->db->table_exists($table)) {
            return;
        }

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
            'id_planning_projects' => [
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
        $this->dbforge->add_key('id_planning_projects');
        $this->dbforge->add_key($targetColumn);

        $this->dbforge->create_table(
            $table,
            true,
            ['engine' => 'InnoDB'],
        );

        $tableName = $this->db->dbprefix($table);

        $this->db->query(
            "ALTER TABLE `{$tableName}`
             ADD UNIQUE KEY `project_target_unique`
             (`id_planning_projects`, `{$targetColumn}`)"
        );
    }

    private function addForeignKey(
        string $table,
        string $constraintSuffix,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete = 'CASCADE'
    ): void {
        $constraint = $this->db->dbprefix . $constraintSuffix;

        $existing = $this->db
            ->query(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME = ?',
                [$constraint],
            )
            ->row_array();

        if ($existing) {
            return;
        }

        $tableName = $this->db->dbprefix($table);
        $referenceName = $this->db->dbprefix($referenceTable);

        $this->db->query(
            "ALTER TABLE `{$tableName}`
             ADD CONSTRAINT `{$constraint}`
             FOREIGN KEY (`{$column}`)
             REFERENCES `{$referenceName}` (`{$referenceColumn}`)
             ON DELETE {$onDelete}
             ON UPDATE CASCADE"
        );
    }
}
