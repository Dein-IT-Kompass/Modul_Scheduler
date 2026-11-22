<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_employee_teams extends EA_Migration
{
    public function up(): void
    {
        $this->createTeamsTable();
        $this->createTeamMembersTable();

        $this->addForeignKeyIfMissing(
            'employee_team_members',
            $this->db->dbprefix . 'employee_team_members_team',
            'id_employee_teams',
            'employee_teams',
            'id',
        );

        $this->addForeignKeyIfMissing(
            'employee_team_members',
            $this->db->dbprefix . 'employee_team_members_user',
            'id_users',
            'users',
            'id',
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table('employee_team_members', true);
        $this->dbforge->drop_table('employee_teams', true);
    }

    private function createTeamsTable(): void
    {
        if ($this->db->table_exists('employee_teams')) {
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
        $this->dbforge->add_key('name');
        $this->dbforge->add_key('is_active');

        $this->dbforge->create_table(
            'employee_teams',
            true,
            ['engine' => 'InnoDB'],
        );
    }

    private function createTeamMembersTable(): void
    {
        if ($this->db->table_exists('employee_team_members')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_employee_teams' => [
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
            'team_role' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'member',
                'null' => false,
            ],
            'can_approve_absences' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'can_manage_calendar' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'can_manage_series' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
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
        $this->dbforge->add_key('id_employee_teams');
        $this->dbforge->add_key('id_users');

        $this->dbforge->create_table(
            'employee_team_members',
            true,
            ['engine' => 'InnoDB'],
        );

        $table = $this->db->dbprefix('employee_team_members');

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD UNIQUE KEY `employee_team_user_unique`
             (`id_employee_teams`, `id_users`)"
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
