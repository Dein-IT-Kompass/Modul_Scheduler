<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_encrypted_calendar_subscription_token extends EA_Migration
{
    public function up(): void
    {
        if (
            $this->db->field_exists(
                'token_encrypted',
                'planning_calendar_subscriptions'
            )
        ) {
            return;
        }

        $this->dbforge->add_column(
            'planning_calendar_subscriptions',
            [
                'token_encrypted' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'token_hash',
                ],
            ]
        );
    }

    public function down(): void
    {
        if (
            $this->db->field_exists(
                'token_encrypted',
                'planning_calendar_subscriptions'
            )
        ) {
            $this->dbforge->drop_column(
                'planning_calendar_subscriptions',
                'token_encrypted'
            );
        }
    }
}
