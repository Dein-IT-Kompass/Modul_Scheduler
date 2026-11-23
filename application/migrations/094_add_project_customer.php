<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_project_customer extends EA_Migration
{
    public function up(): void
    {
        if (
            !$this->db->field_exists(
                'id_users_customer',
                'planning_projects'
            )
        ) {
            $this->dbforge->add_column(
                'planning_projects',
                [
                    'id_users_customer' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => true,
                        'after' => 'id',
                    ],
                ]
            );
        }

        $projects = $this->db->dbprefix(
            'planning_projects'
        );

        $users = $this->db->dbprefix('users');

        $constraint =
            $this->db->dbprefix .
            'planning_projects_customer';

        if (!$this->constraintExists($constraint)) {
            $this->db->query(
                "ALTER TABLE `{$projects}`
                 ADD CONSTRAINT `{$constraint}`
                 FOREIGN KEY (`id_users_customer`)
                 REFERENCES `{$users}` (`id`)
                 ON DELETE SET NULL
                 ON UPDATE CASCADE"
            );
        }
    }

    public function down(): void
    {
        $projects = $this->db->dbprefix(
            'planning_projects'
        );

        $constraint =
            $this->db->dbprefix .
            'planning_projects_customer';

        if ($this->constraintExists($constraint)) {
            $this->db->query(
                "ALTER TABLE `{$projects}`
                 DROP FOREIGN KEY `{$constraint}`"
            );
        }

        if (
            $this->db->field_exists(
                'id_users_customer',
                'planning_projects'
            )
        ) {
            $this->dbforge->drop_column(
                'planning_projects',
                'id_users_customer'
            );
        }
    }

    private function constraintExists(
        string $constraintName
    ): bool {
        $query = $this->db->query(
            'SELECT 1
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ?
               AND CONSTRAINT_NAME = ?
             LIMIT 1',
            [
                $this->db->database,
                $constraintName,
            ]
        );

        return $query->num_rows() > 0;
    }
}
