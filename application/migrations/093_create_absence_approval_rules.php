<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_absence_approval_rules extends EA_Migration
{
    public function up(): void
    {
        if (
            !$this->db->field_exists(
                'absence_approval_mode',
                'users'
            )
        ) {
            $this->dbforge->add_column(
                'users',
                [
                    'absence_approval_mode' => [
                        'type' => 'VARCHAR',
                        'constraint' => 32,
                        'default' => 'approval_required',
                        'null' => false,
                    ],
                ]
            );
        }

        if (
            $this->db->table_exists(
                'employee_absence_approvers'
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

            'id_users_employee' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],

            'id_users_approver' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],

            'can_approve' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false,
            ],

            'can_create_approved' => [
                'type' => 'TINYINT',
                'constraint' => 1,
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
        $this->dbforge->add_key('id_users_employee');
        $this->dbforge->add_key('id_users_approver');
        $this->dbforge->add_key('can_approve');

        $this->dbforge->create_table(
            'employee_absence_approvers',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix(
            'employee_absence_approvers'
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD UNIQUE KEY `absence_employee_approver_unique`
             (`id_users_employee`, `id_users_approver`)"
        );

        $users = $this->db->dbprefix('users');

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$this->db->dbprefix}absence_rule_employee`
             FOREIGN KEY (`id_users_employee`)
             REFERENCES `{$users}` (`id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$this->db->dbprefix}absence_rule_approver`
             FOREIGN KEY (`id_users_approver`)
             REFERENCES `{$users}` (`id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'employee_absence_approvers',
            true
        );

        if (
            $this->db->field_exists(
                'absence_approval_mode',
                'users'
            )
        ) {
            $this->dbforge->drop_column(
                'users',
                'absence_approval_mode'
            );
        }
    }
}
