<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_planning_resource_categories extends EA_Migration
{
    public function up(): void
    {
        $this->createCategoriesTable();
        $this->extendResourcesTable();
        $this->addForeignKeys();
        $this->createDefaultCategories();
        $this->assignExistingResources();
    }

    public function down(): void
    {
        $resources = $this->db->dbprefix('planning_resources');
        $categories = $this->db->dbprefix('planning_resource_categories');

        if ($this->constraintExists(
            $this->db->dbprefix . 'planning_resources_category'
        )) {
            $this->db->query(
                "ALTER TABLE `{$resources}`
                 DROP FOREIGN KEY `{$this->db->dbprefix}planning_resources_category`"
            );
        }

        if (
            $this->db->field_exists(
                'id_planning_resource_categories',
                'planning_resources'
            )
        ) {
            $this->dbforge->drop_column(
                'planning_resources',
                'id_planning_resource_categories'
            );
        }

        if ($this->constraintExists(
            $this->db->dbprefix . 'planning_resource_categories_parent'
        )) {
            $this->db->query(
                "ALTER TABLE `{$categories}`
                 DROP FOREIGN KEY `{$this->db->dbprefix}planning_resource_categories_parent`"
            );
        }

        $this->dbforge->drop_table(
            'planning_resource_categories',
            true
        );
    }

    private function createCategoriesTable(): void
    {
        if (
            $this->db->table_exists(
                'planning_resource_categories'
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => false,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => false,
            ],
            'id_parent' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
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
            'icon' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'default' => 'fa-box',
                'null' => false,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->dbforge->add_key('id_parent');
        $this->dbforge->add_key('is_active');
        $this->dbforge->add_key('sort_order');

        $this->dbforge->create_table(
            'planning_resource_categories',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix(
            'planning_resource_categories'
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD UNIQUE KEY `planning_resource_category_slug_unique`
             (`slug`)"
        );
    }

    private function extendResourcesTable(): void
    {
        if (
            $this->db->field_exists(
                'id_planning_resource_categories',
                'planning_resources'
            )
        ) {
            return;
        }

        $this->dbforge->add_column(
            'planning_resources',
            [
                'id_planning_resource_categories' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'resource_type',
                ],
            ]
        );

        $table = $this->db->dbprefix('planning_resources');

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD KEY `id_planning_resource_categories`
             (`id_planning_resource_categories`)"
        );
    }

    private function addForeignKeys(): void
    {
        $categories = $this->db->dbprefix(
            'planning_resource_categories'
        );
        $resources = $this->db->dbprefix(
            'planning_resources'
        );

        $parentConstraint =
            $this->db->dbprefix .
            'planning_resource_categories_parent';

        if (!$this->constraintExists($parentConstraint)) {
            $this->db->query(
                "ALTER TABLE `{$categories}`
                 ADD CONSTRAINT `{$parentConstraint}`
                 FOREIGN KEY (`id_parent`)
                 REFERENCES `{$categories}` (`id`)
                 ON DELETE SET NULL
                 ON UPDATE CASCADE"
            );
        }

        $resourceConstraint =
            $this->db->dbprefix .
            'planning_resources_category';

        if (!$this->constraintExists($resourceConstraint)) {
            $this->db->query(
                "ALTER TABLE `{$resources}`
                 ADD CONSTRAINT `{$resourceConstraint}`
                 FOREIGN KEY (`id_planning_resource_categories`)
                 REFERENCES `{$categories}` (`id`)
                 ON DELETE SET NULL
                 ON UPDATE CASCADE"
            );
        }
    }

    private function createDefaultCategories(): void
    {
        $now = date('Y-m-d H:i:s');

        $categories = [
            [
                'name' => 'Fahrzeuge',
                'slug' => 'vehicles',
                'color' => '#4f8fd8',
                'icon' => 'fa-truck',
                'sort_order' => 10,
            ],
            [
                'name' => 'Maschinen',
                'slug' => 'machines',
                'color' => '#e09b39',
                'icon' => 'fa-tractor',
                'sort_order' => 20,
            ],
            [
                'name' => 'Geräte und Werkzeuge',
                'slug' => 'equipment',
                'color' => '#8e6bbf',
                'icon' => 'fa-tools',
                'sort_order' => 30,
            ],
            [
                'name' => 'Räume',
                'slug' => 'rooms',
                'color' => '#49a68c',
                'icon' => 'fa-door-open',
                'sort_order' => 40,
            ],
            [
                'name' => 'Arbeitsplätze',
                'slug' => 'workplaces',
                'color' => '#64748b',
                'icon' => 'fa-desktop',
                'sort_order' => 50,
            ],
            [
                'name' => 'Sonstiges',
                'slug' => 'other',
                'color' => '#888888',
                'icon' => 'fa-box',
                'sort_order' => 60,
            ],
        ];

        foreach ($categories as $category) {
            $existing = $this->db
                ->get_where(
                    'planning_resource_categories',
                    ['slug' => $category['slug']]
                )
                ->row_array();

            if ($existing) {
                continue;
            }

            $category['id_parent'] = null;
            $category['description'] = null;
            $category['is_active'] = 1;
            $category['create_datetime'] = $now;
            $category['update_datetime'] = $now;

            $this->db->insert(
                'planning_resource_categories',
                $category
            );
        }
    }

    private function assignExistingResources(): void
    {
        $mapping = [
            'vehicle' => 'vehicles',
            'machine' => 'machines',
            'equipment' => 'equipment',
            'room' => 'rooms',
            'workplace' => 'workplaces',
            'other' => 'other',
        ];

        foreach ($mapping as $type => $slug) {
            $category = $this->db
                ->select('id')
                ->get_where(
                    'planning_resource_categories',
                    ['slug' => $slug]
                )
                ->row_array();

            if (!$category) {
                continue;
            }

            $this->db
                ->where('resource_type', $type)
                ->where(
                    'id_planning_resource_categories IS NULL',
                    null,
                    false
                )
                ->update(
                    'planning_resources',
                    [
                        'id_planning_resource_categories' =>
                            (int) $category['id'],
                    ]
                );
        }
    }

    private function constraintExists(
        string $constraint
    ): bool {
        return (bool) $this->db
            ->query(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME = ?',
                [$constraint]
            )
            ->row_array();
    }
}
