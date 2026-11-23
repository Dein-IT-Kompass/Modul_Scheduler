<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_impacts extends EA_Migration
{
    public function up(): void
    {
        $this->createResourceUnavailabilities();
        $this->createAssignmentImpacts();
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'planning_assignment_impacts',
            true
        );

        $this->dbforge->drop_table(
            'planning_resource_unavailabilities',
            true
        );
    }

    private function createResourceUnavailabilities(): void
    {
        if (
            $this->db->table_exists(
                'planning_resource_unavailabilities'
            )
        ) {
            return;
        }

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

            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
            ],

            'start_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],

            'end_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'active',
                'null' => false,
            ],

            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => true,
            ],

            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'id_users_created_by' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->dbforge->add_key(
            'id_planning_resources'
        );
        $this->dbforge->add_key('status');
        $this->dbforge->add_key('start_datetime');
        $this->dbforge->add_key('end_datetime');

        $this->dbforge->create_table(
            'planning_resource_unavailabilities',
            true,
            ['engine' => 'InnoDB']
        );
    }

    private function createAssignmentImpacts(): void
    {
        if (
            $this->db->table_exists(
                'planning_assignment_impacts'
            )
        ) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'id_planning_assignments' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],

            'impact_type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],

            'source_type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],

            'source_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],

            'severity' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'warning',
                'null' => false,
            ],

            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'open',
                'null' => false,
            ],

            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'decision' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],

            'decision_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'id_users_decided_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],

            'detected_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],

            'resolved_datetime' => [
                'type' => 'DATETIME',
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
        $this->dbforge->add_key(
            'id_planning_assignments'
        );
        $this->dbforge->add_key('impact_type');
        $this->dbforge->add_key('source_type');
        $this->dbforge->add_key('source_id');
        $this->dbforge->add_key('severity');
        $this->dbforge->add_key('status');

        $this->dbforge->create_table(
            'planning_assignment_impacts',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix(
            'planning_assignment_impacts'
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD UNIQUE KEY `planning_impact_source_unique`
             (
                `id_planning_assignments`,
                `source_type`,
                `source_id`
             )"
        );
    }
}
