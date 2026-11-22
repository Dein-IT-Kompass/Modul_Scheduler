<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_assignments extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('planning_assignments')) {
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
            'id_planning_project_phases' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => false,
            ],
            'start_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'end_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'location' => [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ],
            'priority' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 70,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'planned',
                'null' => false,
            ],
            'notes' => [
                'type' => 'TEXT',
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
        $this->dbforge->add_key('id_planning_projects');
        $this->dbforge->add_key('id_planning_project_phases');
        $this->dbforge->add_key('status');
        $this->dbforge->add_key('priority');
        $this->dbforge->add_key([
            'start_datetime',
            'end_datetime',
        ]);

        $this->dbforge->create_table(
            'planning_assignments',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix(
            'planning_assignments'
        );

        $projects = $this->db->dbprefix(
            'planning_projects'
        );

        $phases = $this->db->dbprefix(
            'planning_project_phases'
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$this->db->dbprefix}planning_assignments_project`
             FOREIGN KEY (`id_planning_projects`)
             REFERENCES `{$projects}` (`id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$this->db->dbprefix}planning_assignments_phase`
             FOREIGN KEY (`id_planning_project_phases`)
             REFERENCES `{$phases}` (`id`)
             ON DELETE SET NULL
             ON UPDATE CASCADE"
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'planning_assignments',
            true
        );
    }
}
