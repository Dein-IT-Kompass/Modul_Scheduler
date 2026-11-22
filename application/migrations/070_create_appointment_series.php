<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_appointment_series extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('appointment_series')) {
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
                ],
                'id_services' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'id_users_provider' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'start_date' => [
                    'type' => 'DATE',
                ],
                'recurrence_interval' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                ],
                'end_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'count',
                ],
                'occurrence_count' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'end_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'location' => [
                    'type' => 'VARCHAR',
                    'constraint' => 256,
                    'null' => true,
                ],
                'color' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'create_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'update_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_services');
            $this->dbforge->add_key('id_users_provider');

            $this->dbforge->create_table(
                'appointment_series',
                true,
                ['engine' => 'InnoDB']
            );
        }

        if (!$this->db->table_exists('appointment_series_slots')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_appointment_series' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'weekday' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                ],
                'start_time' => [
                    'type' => 'TIME',
                ],
                'duration_minutes' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_appointment_series');
            $this->dbforge->add_key(['id_appointment_series', 'weekday']);

            $this->dbforge->create_table(
                'appointment_series_slots',
                true,
                ['engine' => 'InnoDB']
            );
        }

        if (!$this->db->field_exists('id_appointment_series', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'id_appointment_series' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'series_sequence' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
            ]);
        }

        $prefix = $this->db->dbprefix;

        $this->addForeignKeyIfMissing(
            'appointment_series_slots',
            $prefix . 'appointment_series_slots_series',
            'id_appointment_series',
            'appointment_series',
            'id',
            'CASCADE'
        );

        $this->addForeignKeyIfMissing(
            'appointments',
            $prefix . 'appointments_series',
            'id_appointment_series',
            'appointment_series',
            'id',
            'SET NULL'
        );
    }

    public function down(): void
    {
        if ($this->db->field_exists('id_appointment_series', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'series_sequence');
            $this->dbforge->drop_column('appointments', 'id_appointment_series');
        }

        $this->dbforge->drop_table('appointment_series_slots', true);
        $this->dbforge->drop_table('appointment_series', true);
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $constraint,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete
    ): void {
        $tableName = $this->db->dbprefix($table);
        $referenceTableName = $this->db->dbprefix($referenceTable);

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

        $allowedDeleteActions = ['CASCADE', 'SET NULL', 'RESTRICT'];

        if (!in_array($onDelete, $allowedDeleteActions, true)) {
            throw new InvalidArgumentException(
                'Invalid foreign-key delete action: ' . $onDelete,
            );
        }

        $this->db->query(
            "ALTER TABLE `{$tableName}`
             ADD CONSTRAINT `{$constraint}`
             FOREIGN KEY (`{$column}`)
             REFERENCES `{$referenceTableName}` (`{$referenceColumn}`)
             ON DELETE {$onDelete}
             ON UPDATE CASCADE"
        );
    }
}
