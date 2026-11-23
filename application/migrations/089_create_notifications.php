<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_notifications extends EA_Migration
{
    public function up(): void
    {
        $this->createNotifications();
        $this->createRecipients();
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'notification_recipients',
            true
        );

        $this->dbforge->drop_table(
            'notifications',
            true
        );
    }

    private function createNotifications(): void
    {
        if ($this->db->table_exists('notifications')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],

            'severity' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'info',
                'null' => false,
            ],

            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => false,
            ],

            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],

            'entity_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],

            'source_type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],

            'source_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],

            'action_url' => [
                'type' => 'VARCHAR',
                'constraint' => 1024,
                'null' => true,
            ],

            'deduplication_key' => [
                'type' => 'VARCHAR',
                'constraint' => 256,
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
        $this->dbforge->add_key('type');
        $this->dbforge->add_key('severity');
        $this->dbforge->add_key('entity_type');
        $this->dbforge->add_key('entity_id');
        $this->dbforge->add_key('source_type');
        $this->dbforge->add_key('source_id');
        $this->dbforge->add_key('is_active');

        $this->dbforge->create_table(
            'notifications',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix('notifications');

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD UNIQUE KEY `notification_deduplication_unique`
             (`deduplication_key`)"
        );
    }

    private function createRecipients(): void
    {
        if (
            $this->db->table_exists(
                'notification_recipients'
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

            'id_notifications' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],

            'id_users' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],

            'state' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'active',
                'null' => false,
            ],

            'is_read' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],

            'read_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'snoozed_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'reminder_interval' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'none',
                'null' => false,
            ],

            'next_reminder_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'is_pinned' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],

            'deleted_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'last_displayed_datetime' => [
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
        $this->dbforge->add_key('id_notifications');
        $this->dbforge->add_key('id_users');
        $this->dbforge->add_key('state');
        $this->dbforge->add_key('is_read');
        $this->dbforge->add_key('snoozed_until');
        $this->dbforge->add_key(
            'next_reminder_datetime'
        );
        $this->dbforge->add_key('deleted_datetime');

        $this->dbforge->create_table(
            'notification_recipients',
            true,
            ['engine' => 'InnoDB']
        );

        $table = $this->db->dbprefix(
            'notification_recipients'
        );

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD UNIQUE KEY `notification_recipient_unique`
             (`id_notifications`, `id_users`)"
        );
    }
}
