<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_resources extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('planning_resources')) {
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
            'resource_type' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'equipment',
                'null' => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'location' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => true,
            ],
            'inventory_number' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
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
        $this->dbforge->add_key('resource_type');
        $this->dbforge->add_key('is_active');

        $this->dbforge->create_table(
            'planning_resources',
            true,
            ['engine' => 'InnoDB'],
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table('planning_resources', true);
    }
}
