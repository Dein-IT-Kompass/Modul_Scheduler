<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_resource_location_behavior extends EA_Migration
{
    public function up(): void
    {
        if (
            !$this->db->field_exists(
                'location_behavior',
                'planning_resources'
            )
        ) {
            $this->dbforge->add_column(
                'planning_resources',
                [
                    'location_behavior' => [
                        'type' => 'VARCHAR',
                        'constraint' => 32,
                        'default' => 'mobile',
                        'null' => false,
                        'after' => 'location',
                    ],
                ]
            );
        }

        if (
            !$this->db->field_exists(
                'id_carrier_resource',
                'planning_resources'
            )
        ) {
            $this->dbforge->add_column(
                'planning_resources',
                [
                    'id_carrier_resource' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'after' => 'location_behavior',
                    ],
                ]
            );
        }

        $table = $this->db->dbprefix('planning_resources');

        $index = $this->db
            ->query(
                "SHOW INDEX FROM `{$table}`
                 WHERE Key_name = 'id_carrier_resource'"
            )
            ->row_array();

        if (!$index) {
            $this->db->query(
                "ALTER TABLE `{$table}`
                 ADD KEY `id_carrier_resource`
                 (`id_carrier_resource`)"
            );
        }

        $constraint =
            $this->db->dbprefix .
            'planning_resources_carrier';

        $existing = $this->db
            ->query(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME = ?',
                [$constraint]
            )
            ->row_array();

        if (!$existing) {
            $this->db->query(
                "ALTER TABLE `{$table}`
                 ADD CONSTRAINT `{$constraint}`
                 FOREIGN KEY (`id_carrier_resource`)
                 REFERENCES `{$table}` (`id`)
                 ON DELETE SET NULL
                 ON UPDATE CASCADE"
            );
        }

        // Sinnvolle Ausgangswerte für vorhandene Ressourcen.
        $this->db
            ->where('resource_type', 'room')
            ->update(
                'planning_resources',
                ['location_behavior' => 'stationary']
            );

        $this->db
            ->where('resource_type', 'workplace')
            ->update(
                'planning_resources',
                ['location_behavior' => 'stationary']
            );

        $this->db
            ->where('resource_type', 'vehicle')
            ->update(
                'planning_resources',
                ['location_behavior' => 'mobile']
            );

        $this->db
            ->where_in(
                'resource_type',
                ['machine', 'equipment', 'other']
            )
            ->update(
                'planning_resources',
                ['location_behavior' => 'mobile']
            );
    }

    public function down(): void
    {
        $table = $this->db->dbprefix('planning_resources');

        $constraint =
            $this->db->dbprefix .
            'planning_resources_carrier';

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
            $this->db->query(
                "ALTER TABLE `{$table}`
                 DROP FOREIGN KEY `{$constraint}`"
            );
        }

        if (
            $this->db->field_exists(
                'id_carrier_resource',
                'planning_resources'
            )
        ) {
            $this->dbforge->drop_column(
                'planning_resources',
                'id_carrier_resource'
            );
        }

        if (
            $this->db->field_exists(
                'location_behavior',
                'planning_resources'
            )
        ) {
            $this->dbforge->drop_column(
                'planning_resources',
                'location_behavior'
            );
        }
    }
}
