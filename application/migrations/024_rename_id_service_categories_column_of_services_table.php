<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

class Migration_Rename_id_service_categories_column_of_services_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if ($this->db->field_exists('id_service_categories', 'services')) {
            $old_constraint =
                $this->db->dbprefix === 'ea_'
                    ? 'services_service_categories'
                    : $this->db->dbprefix('services') . '_ibfk_1';

            $new_constraint =
                $this->db->dbprefix === 'ea_'
                    ? 'services_categories'
                    : $this->db->dbprefix('services_categories');

            $this->db->query(
                'ALTER TABLE `' .
                    $this->db->dbprefix('services') .
                    '` DROP FOREIGN KEY `' .
                    $old_constraint .
                    '`',
            );

            $fields = [
                'id_service_categories' => [
                    'name' => 'id_categories',
                    'type' => 'INT',
                    'constraint' => '11',
                ],
            ];

            $this->dbforge->modify_column('services', $fields);

            $this->db->query(
                '
                ALTER TABLE `' .
                    $this->db->dbprefix('services') .
                    '`
                    ADD CONSTRAINT `' .
                    $new_constraint .
                    '` FOREIGN KEY (`id_categories`) REFERENCES `' .
                    $this->db->dbprefix('categories') .
                    '` (`id`)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            ',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('id_categories', 'services')) {
            $current_constraint =
                $this->db->dbprefix === 'ea_'
                    ? 'services_categories'
                    : $this->db->dbprefix('services_categories');

            $restored_constraint =
                $this->db->dbprefix === 'ea_'
                    ? 'services_service_categories'
                    : $this->db->dbprefix('services_service_categories');

            $this->db->query(
                'ALTER TABLE `' .
                    $this->db->dbprefix('services') .
                    '` DROP FOREIGN KEY `' .
                    $current_constraint .
                    '`',
            );

            $fields = [
                'id_categories' => [
                    'name' => 'id_service_categories',
                    'type' => 'INT',
                    'constraint' => '11',
                ],
            ];

            $this->dbforge->modify_column('services', $fields);

            $this->db->query(
                '
                ALTER TABLE `' .
                    $this->db->dbprefix('services') .
                    '`
                    ADD CONSTRAINT `' .
                    $restored_constraint .
                    '` FOREIGN KEY (`id_service_categories`) REFERENCES `' .
                    $this->db->dbprefix('categories') .
                    '` (`id`)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            ',
            );
        }
    }
}
