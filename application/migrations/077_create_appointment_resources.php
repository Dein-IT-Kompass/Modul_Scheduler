<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_appointment_resources extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('appointment_resources')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_appointments' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'id_planning_resources' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'location_mode' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'appointment',
                    'null' => false,
                ],
                'location_override' => [
                    'type' => 'VARCHAR',
                    'constraint' => 512,
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
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->add_key('id_planning_resources');

            $this->dbforge->create_table(
                'appointment_resources',
                true,
                ['engine' => 'InnoDB']
            );

            $table = $this->db->dbprefix('appointment_resources');

            $this->db->query(
                "ALTER TABLE `{$table}`
                 ADD UNIQUE KEY `appointment_resource_unique`
                 (`id_appointments`, `id_planning_resources`)"
            );
        }

        $this->addForeignKeyIfMissing(
            'appointment_resources',
            $this->db->dbprefix . 'appointment_resources_appointment',
            'id_appointments',
            'appointments',
            'id'
        );

        $this->addForeignKeyIfMissing(
            'appointment_resources',
            $this->db->dbprefix . 'appointment_resources_resource',
            'id_planning_resources',
            'planning_resources',
            'id'
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'appointment_resources',
            true
        );
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $constraint,
        string $column,
        string $referenceTable,
        string $referenceColumn
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

        $tableName = $this->db->dbprefix($table);
        $referenceTableName =
            $this->db->dbprefix($referenceTable);

        $this->db->query(
            "ALTER TABLE `{$tableName}`
             ADD CONSTRAINT `{$constraint}`
             FOREIGN KEY (`{$column}`)
             REFERENCES `{$referenceTableName}` (`{$referenceColumn}`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );
    }
}
