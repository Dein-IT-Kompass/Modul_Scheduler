<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_appointment_series_participants extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('appointment_series_participants')) {
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
                    'null' => false,
                ],
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'employee',
                    'null' => false,
                ],
                'availability_required' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
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
            $this->dbforge->add_key('id_appointment_series');
            $this->dbforge->add_key('id_users');
            $this->dbforge->add_key(
                ['id_appointment_series', 'id_users'],
                false,
                true
            );

            $this->dbforge->create_table(
                'appointment_series_participants',
                true,
                ['engine' => 'InnoDB']
            );
        }

        $prefix = $this->db->dbprefix;

        $this->addForeignKeyIfMissing(
            'appointment_series_participants',
            $prefix . 'series_participants_series',
            'id_appointment_series',
            'appointment_series',
            'id'
        );

        $this->addForeignKeyIfMissing(
            'appointment_series_participants',
            $prefix . 'series_participants_users',
            'id_users',
            'users',
            'id'
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'appointment_series_participants',
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
                [$constraint],
            )
            ->row_array();

        if ($existing) {
            return;
        }

        $tableName = $this->db->dbprefix($table);
        $referenceTableName = $this->db->dbprefix($referenceTable);

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
