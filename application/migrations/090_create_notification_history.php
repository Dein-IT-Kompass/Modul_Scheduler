<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_notification_history extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('notification_history')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'id_notification_recipients' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
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

            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],

            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'create_datetime' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->dbforge->add_key('id', true);

        $this->dbforge->add_key(
            'id_notification_recipients'
        );

        $this->dbforge->add_key(
            'id_notifications'
        );

        $this->dbforge->add_key('id_users');
        $this->dbforge->add_key('action');
        $this->dbforge->add_key('create_datetime');

        $this->dbforge->create_table(
            'notification_history',
            true,
            ['engine' => 'InnoDB']
        );

        $this->addForeignKeys();
    }

    public function down(): void
    {
        $this->dbforge->drop_table(
            'notification_history',
            true
        );
    }

    private function addForeignKeys(): void
    {
        $history = $this->db->dbprefix(
            'notification_history'
        );

        $recipients = $this->db->dbprefix(
            'notification_recipients'
        );

        $notifications = $this->db->dbprefix(
            'notifications'
        );

        $this->addConstraint(
            $history,
            $this->db->dbprefix .
                'notification_history_recipient',
            'id_notification_recipients',
            $recipients
        );

        $this->addConstraint(
            $history,
            $this->db->dbprefix .
                'notification_history_notification',
            'id_notifications',
            $notifications
        );
    }

    private function addConstraint(
        string $table,
        string $constraint,
        string $column,
        string $referenceTable
    ): void {
        $exists = $this->db
            ->query(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME = ?',
                [$constraint]
            )
            ->row_array();

        if ($exists) {
            return;
        }

        $this->db->query(
            "ALTER TABLE `{$table}`
             ADD CONSTRAINT `{$constraint}`
             FOREIGN KEY (`{$column}`)
             REFERENCES `{$referenceTable}` (`id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );
    }
}
