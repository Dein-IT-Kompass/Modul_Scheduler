<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_project_phases extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('planning_project_phases')) {
            return;
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

            'id_parent' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
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

            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],

            'end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],

            'progress_percent' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'default' => 0,
                'null' => false,
            ],

            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'planned',
                'null' => false,
            ],

            'color' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => '#7cbae8',
                'null' => false,
            ],

            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->dbforge->add_key('id_planning_projects');
        $this->dbforge->add_key('id_parent');
        $this->dbforge->add_key('status');
        $this->dbforge->add_key(['start_date', 'end_date']);

        $this->dbforge->create_table(
            'planning_project_phases',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix(
            'planning_project_phases'
        );

        $projects = $this->db->dbprefix(
            'planning_projects'
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$this->db->dbprefix}project_phases_project`
             FOREIGN KEY (`id_planning_projects`)
             REFERENCES `{$projects}` (`id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$this->db->dbprefix}project_phases_parent`
             FOREIGN KEY (`id_parent`)
             REFERENCES `{$table}` (`id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'planning_project_phases',
            true
        );
    }
}
