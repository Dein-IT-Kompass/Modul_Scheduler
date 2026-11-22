<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_resource_allocations extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('planning_resource_allocations')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_planning_resources' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'allocation_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'project',
                    'null' => false,
                ],
                'id_planning_projects' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'id_appointments' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'start_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'end_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'priority' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 50,
                    'null' => false,
                ],
                'location' => [
                    'type' => 'VARCHAR',
                    'constraint' => 512,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'active',
                    'null' => false,
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
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
            $this->dbforge->add_key('id_planning_resources');
            $this->dbforge->add_key('id_planning_projects');
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->add_key('allocation_type');
            $this->dbforge->add_key('status');
            $this->dbforge->add_key('priority');
            $this->dbforge->add_key([
                'id_planning_resources',
                'start_datetime',
                'end_datetime',
            ]);

            $this->dbforge->create_table(
                'planning_resource_allocations',
                true,
                ['engine' => 'InnoDB']
            );
        }

        $this->addForeignKeyIfMissing(
            'planning_resource_allocations',
            $this->db->dbprefix . 'resource_allocations_resource',
            'id_planning_resources',
            'planning_resources',
            'id',
            'CASCADE'
        );

        $this->addForeignKeyIfMissing(
            'planning_resource_allocations',
            $this->db->dbprefix . 'resource_allocations_project',
            'id_planning_projects',
            'planning_projects',
            'id',
            'SET NULL'
        );

        $this->addForeignKeyIfMissing(
            'planning_resource_allocations',
            $this->db->dbprefix . 'resource_allocations_appointment',
            'id_appointments',
            'appointments',
            'id',
            'SET NULL'
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'planning_resource_allocations',
            true
        );
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $constraint,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete
    ): void {
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

        $allowedActions = ['CASCADE', 'SET NULL', 'RESTRICT'];

        if (!in_array($onDelete, $allowedActions, true)) {
            throw new InvalidArgumentException(
                'Ungültige Löschregel für Foreign Key.'
            );
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
