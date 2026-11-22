<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Extend_appointment_series extends EA_Migration
{
    public function up(): void
    {
        $this->extendAppointmentSeries();
        $this->extendAppointmentSeriesSlots();
        $this->extendAppointments();
    }

    public function down(): void
    {
        $this->dropColumnsIfPresent('appointments', [
            'course_state',
            'visibility',
            'booking_mode',
            'capacity_mode',
            'capacity',
        ]);

        $this->dropColumnsIfPresent('appointment_series_slots', [
            'update_datetime',
            'create_datetime',
            'sort_order',
        ]);

        $this->dropColumnsIfPresent('appointment_series', [
            'course_state',
            'visibility',
            'capacity_mode',
            'capacity',
            'notes',
            'duration_minutes',
            'start_time',
            'recurrence_type',
            'group_name',
        ]);
    }

    private function extendAppointmentSeries(): void
    {
        $fields = [];

        if (!$this->db->field_exists('group_name', 'appointment_series')) {
            $fields['group_name'] = [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => true,
            ];
        }

        if (!$this->db->field_exists('recurrence_type', 'appointment_series')) {
            $fields['recurrence_type'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'weekly',
                'null' => false,
            ];
        }

        if (!$this->db->field_exists('start_time', 'appointment_series')) {
            $fields['start_time'] = [
                'type' => 'TIME',
                'null' => true,
            ];
        }

        if (!$this->db->field_exists('duration_minutes', 'appointment_series')) {
            $fields['duration_minutes'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 60,
                'null' => false,
            ];
        }

        if (!$this->db->field_exists('notes', 'appointment_series')) {
            $fields['notes'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }

        if (!$this->db->field_exists('capacity', 'appointment_series')) {
            $fields['capacity'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ];
        }

        if (!$this->db->field_exists('capacity_mode', 'appointment_series')) {
            $fields['capacity_mode'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'unlimited',
                'null' => false,
            ];
        }

        if (!$this->db->field_exists('visibility', 'appointment_series')) {
            $fields['visibility'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'public',
                'null' => false,
            ];
        }

        if (!$this->db->field_exists('course_state', 'appointment_series')) {
            $fields['course_state'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'tentative',
                'null' => false,
            ];
        }

        if ($fields) {
            $this->dbforge->add_column('appointment_series', $fields);
        }
    }

    private function extendAppointmentSeriesSlots(): void
    {
        $fields = [];

        if (!$this->db->field_exists('sort_order', 'appointment_series_slots')) {
            $fields['sort_order'] = [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => false,
            ];
        }

        if (!$this->db->field_exists('create_datetime', 'appointment_series_slots')) {
            $fields['create_datetime'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if (!$this->db->field_exists('update_datetime', 'appointment_series_slots')) {
            $fields['update_datetime'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if ($fields) {
            $this->dbforge->add_column('appointment_series_slots', $fields);
        }
    }

    private function extendAppointments(): void
    {
        $fields = [];

        if (!$this->db->field_exists('capacity', 'appointments')) {
            $fields['capacity'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ];
        }

        if (!$this->db->field_exists('capacity_mode', 'appointments')) {
            $fields['capacity_mode'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'unlimited',
                'null' => false,
            ];
        }

        if (!$this->db->field_exists('booking_mode', 'appointments')) {
            $fields['booking_mode'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'individual',
                'null' => false,
            ];
        }

        if (!$this->db->field_exists('visibility', 'appointments')) {
            $fields['visibility'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'public',
                'null' => false,
            ];
        }

        if (!$this->db->field_exists('course_state', 'appointments')) {
            $fields['course_state'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'confirmed',
                'null' => false,
            ];
        }

        if ($fields) {
            $this->dbforge->add_column('appointments', $fields);
        }

        $this->db
            ->where('id_appointment_series IS NOT NULL', null, false)
            ->update('appointments', [
                'booking_mode' => 'group',
                'course_state' => 'tentative',
            ]);
    }

    private function dropColumnsIfPresent(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            if ($this->db->field_exists($column, $table)) {
                $this->dbforge->drop_column($table, $column);
            }
        }
    }
}
