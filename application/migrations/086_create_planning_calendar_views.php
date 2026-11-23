<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_calendar_views extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('planning_calendar_views')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_users_owner' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 256,
                    'null' => false,
                ],
                'is_shared' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
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
            $this->dbforge->add_key('id_users_owner');
            $this->dbforge->add_key('is_active');

            $this->dbforge->create_table(
                'planning_calendar_views',
                true,
                ['engine' => 'InnoDB']
            );
        }

        if (
            !$this->db->table_exists(
                'planning_calendar_view_calendars'
            )
        ) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_planning_calendar_views' => [
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
                'id_planning_calendar_views'
            );
            $this->dbforge->add_key(
                'id_planning_calendars'
            );

            $this->dbforge->create_table(
                'planning_calendar_view_calendars',
                true,
                ['engine' => 'InnoDB']
            );

            $table = $this->db->dbprefix(
                'planning_calendar_view_calendars'
            );

            $this->db->query(
                "ALTER TABLE `{$table}`
                 ADD UNIQUE KEY `calendar_view_calendar_unique`
                 (
                    `id_planning_calendar_views`,
                    `id_planning_calendars`
                 )"
            );
        }

        $this->addForeignKeys();
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'planning_calendar_view_calendars',
            true
        );

        $this->dbforge->drop_table(
            'planning_calendar_views',
            true
        );
    }

    private function addForeignKeys(): void
    {
        $views = $this->db->dbprefix(
            'planning_calendar_views'
        );

        $relations = $this->db->dbprefix(
            'planning_calendar_view_calendars'
        );

        $calendars = $this->db->dbprefix(
            'planning_calendars'
        );

        $users = $this->db->dbprefix('users');

        $this->addConstraint(
            $views,
            $this->db->dbprefix .
                'calendar_views_owner',
            'id_users_owner',
            $users,
            'id',
            'SET NULL'
        );

        $this->addConstraint(
            $relations,
            $this->db->dbprefix .
                'calendar_view_calendars_view',
            'id_planning_calendar_views',
            $views,
            'id',
            'CASCADE'
        );

        $this->addConstraint(
            $relations,
            $this->db->dbprefix .
                'calendar_view_calendars_calendar',
            'id_planning_calendars',
            $calendars,
            'id',
            'CASCADE'
        );
    }

    private function addConstraint(
        string $table,
        string $constraint,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete
    ): void {
        $exists = $this->db
            ->query(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME = ?',
                [$constraint]
            )
            ->row_array();

        if ($exists) {
            return;
        }

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$constraint}`
             FOREIGN KEY (`{$column}`)
             REFERENCES `{$referenceTable}`
             (`{$referenceColumn}`)
             ON DELETE {$onDelete}
             ON UPDATE CASCADE"
        );
    }
}
