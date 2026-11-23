<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_provider_employment_period extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->field_exists('employment_start', 'users')) {
            $this->dbforge->add_column('users', [
                'employment_start' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
            ]);
        }

        if (!$this->db->field_exists('employment_end', 'users')) {
            $this->dbforge->add_column('users', [
                'employment_end' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->field_exists('employment_end', 'users')) {
            $this->dbforge->drop_column('users', 'employment_end');
        }

        if ($this->db->field_exists('employment_start', 'users')) {
            $this->dbforge->drop_column('users', 'employment_start');
        }
    }
}
