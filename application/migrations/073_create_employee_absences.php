<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_employee_absences extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('employee_absences')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_users_employee' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'absence_type' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'end_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'all_day' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'requested',
                'null' => false,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'requested_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'requested_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'decided_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'decided_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'decision_note' => [
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
        $this->dbforge->add_key('id_users_employee');
        $this->dbforge->add_key('status');
        $this->dbforge->add_key(['start_date', 'end_date']);

        $this->dbforge->create_table(
            'employee_absences',
            true,
            ['engine' => 'InnoDB']
        );

        $this->addForeignKey(
            'employee_absences',
            $this->db->dbprefix . 'employee_absences_employee',
            'id_users_employee',
            'users',
            'id'
        );

        $this->addForeignKey(
            'employee_absences',
            $this->db->dbprefix . 'employee_absences_requested_by',
            'requested_by',
            'users',
            'id'
        );

        $this->addForeignKey(
            'employee_absences',
            $this->db->dbprefix . 'employee_absences_decided_by',
            'decided_by',
            'users',
            'id',
            'SET NULL'
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table('employee_absences', true);
    }

    private function addForeignKey(
        string $table,
        string $constraint,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete = 'CASCADE'
    ): void {
        $tableName = $this->db->dbprefix($table);
        $referenceTableName = $this->db->dbprefix($referenceTable);

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
