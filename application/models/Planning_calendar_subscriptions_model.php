<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_calendar_subscriptions_model extends EA_Model
{
    public function status_for_employee(int $employee_id): array
    {
        $calendar = $this->find_or_create_employee_calendar(
            $employee_id
        );

        $subscription = $this->db
            ->select(
                'id,
                 name,
                 is_active,
                 expires_datetime,
                 last_access_datetime,
                 create_datetime,
                 update_datetime'
            )
            ->where(
                'id_planning_calendars',
                (int) $calendar['id']
            )
            ->order_by('id', 'DESC')
            ->get('planning_calendar_subscriptions')
            ->row_array();

        return [
            'calendar' => $calendar,
            'subscription' => $subscription ?: null,
        ];
    }

    public function create_for_employee(
        int $employee_id
    ): array {
        $calendar = $this->find_or_create_employee_calendar(
            $employee_id
        );

        $this->disable_for_calendar(
            (int) $calendar['id']
        );

        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');

        $this->db->insert(
            'planning_calendar_subscriptions',
            [
                'id_planning_calendars' =>
                    (int) $calendar['id'],
                'id_planning_calendar_views' => null,
                'name' =>
                    'Einsatzkalender ' . $calendar['name'],
                'token_hash' => hash('sha256', $token),
                'is_active' => 1,
                'expires_datetime' => null,
                'last_access_datetime' => null,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]
        );

        return [
            'subscription_id' =>
                (int) $this->db->insert_id(),
            'token' => $token,
            'calendar' => $calendar,
        ];
    }

    public function rotate_for_employee(
        int $employee_id
    ): array {
        return $this->create_for_employee($employee_id);
    }

    public function disable_for_employee(
        int $employee_id
    ): void {
        $calendar = $this->find_employee_calendar(
            $employee_id
        );

        if (!$calendar) {
            return;
        }

        $this->disable_for_calendar(
            (int) $calendar['id']
        );
    }

    public function find_active_by_token(
        string $token
    ): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return null;
        }

        $subscription = $this->db
            ->select(
                'planning_calendar_subscriptions.*,
                 planning_calendars.name AS calendar_name,
                 planning_calendars.entity_type,
                 planning_calendars.entity_id,
                 planning_calendars.color'
            )
            ->from('planning_calendar_subscriptions')
            ->join(
                'planning_calendars',
                'planning_calendars.id =
                    planning_calendar_subscriptions.id_planning_calendars',
                'left'
            )
            ->where(
                'planning_calendar_subscriptions.token_hash',
                hash('sha256', strtolower($token))
            )
            ->where(
                'planning_calendar_subscriptions.is_active',
                1
            )
            ->get()
            ->row_array();

        if (!$subscription) {
            return null;
        }

        if (
            !empty($subscription['expires_datetime']) &&
            strtotime($subscription['expires_datetime']) < time()
        ) {
            return null;
        }

        $this->db
            ->where('id', (int) $subscription['id'])
            ->update(
                'planning_calendar_subscriptions',
                [
                    'last_access_datetime' =>
                        date('Y-m-d H:i:s'),
                ]
            );

        return $subscription;
    }

    public function get_assignment_events(
        int $calendar_id
    ): array {
        $assignments = $this->db
            ->distinct()
            ->select(
                'planning_assignments.*,
                 planning_projects.name AS project_name,
                 planning_project_phases.name AS phase_name'
            )
            ->from('planning_calendar_event_calendars')
            ->join(
                'planning_calendar_events',
                'planning_calendar_events.id =
                    planning_calendar_event_calendars.id_planning_calendar_events',
                'inner'
            )
            ->join(
                'planning_assignments',
                'planning_assignments.id =
                    planning_calendar_events.reference_id',
                'inner'
            )
            ->join(
                'planning_projects',
                'planning_projects.id =
                    planning_assignments.id_planning_projects',
                'inner'
            )
            ->join(
                'planning_project_phases',
                'planning_project_phases.id =
                    planning_assignments.id_planning_project_phases',
                'left'
            )
            ->where(
                'planning_calendar_event_calendars.id_planning_calendars',
                $calendar_id
            )
            ->where(
                'planning_calendar_events.event_type',
                'project_assignment'
            )
            ->where(
                'planning_assignments.status !=',
                'cancelled'
            )
            ->order_by(
                'planning_assignments.start_datetime',
                'ASC'
            )
            ->get()
            ->result_array();

        foreach ($assignments as &$assignment) {
            $assignment['resources'] = $this->db
                ->select('planning_resources.name')
                ->from('planning_assignment_resources')
                ->join(
                    'planning_resources',
                    'planning_resources.id =
                        planning_assignment_resources.id_planning_resources',
                    'inner'
                )
                ->where(
                    'planning_assignment_resources.id_planning_assignments',
                    (int) $assignment['id']
                )
                ->order_by('planning_resources.name', 'ASC')
                ->get()
                ->result_array();

            $assignment['teams'] = $this->db
                ->select('employee_teams.name')
                ->from('planning_assignment_teams')
                ->join(
                    'employee_teams',
                    'employee_teams.id =
                        planning_assignment_teams.id_employee_teams',
                    'inner'
                )
                ->where(
                    'planning_assignment_teams.id_planning_assignments',
                    (int) $assignment['id']
                )
                ->order_by('employee_teams.name', 'ASC')
                ->get()
                ->result_array();
        }

        unset($assignment);

        return $assignments;
    }

    private function find_employee_calendar(
        int $employee_id
    ): ?array {
        $calendar = $this->db
            ->where('entity_type', 'employee')
            ->where('entity_id', $employee_id)
            ->get('planning_calendars')
            ->row_array();

        return $calendar ?: null;
    }

    private function find_or_create_employee_calendar(
        int $employee_id
    ): array {
        $calendar = $this->find_employee_calendar(
            $employee_id
        );

        if ($calendar) {
            return $calendar;
        }

        $employee = $this->db
            ->select('id, first_name, last_name')
            ->get_where(
                'users',
                ['id' => $employee_id]
            )
            ->row_array();

        if (!$employee) {
            throw new RuntimeException(
                'Mitarbeiter wurde nicht gefunden.'
            );
        }

        $name = trim(
            ($employee['first_name'] ?? '') .
            ' ' .
            ($employee['last_name'] ?? '')
        );

        $now = date('Y-m-d H:i:s');

        $this->db->insert(
            'planning_calendars',
            [
                'entity_type' => 'employee',
                'entity_id' => $employee_id,
                'name' => $name ?: 'Mitarbeiterkalender',
                'color' => '#2878c8',
                'is_active' => 1,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]
        );

        return $this->db
            ->get_where(
                'planning_calendars',
                ['id' => (int) $this->db->insert_id()]
            )
            ->row_array();
    }

    private function disable_for_calendar(
        int $calendar_id
    ): void {
        $this->db
            ->where(
                'id_planning_calendars',
                $calendar_id
            )
            ->where('is_active', 1)
            ->update(
                'planning_calendar_subscriptions',
                [
                    'is_active' => 0,
                    'update_datetime' =>
                        date('Y-m-d H:i:s'),
                ]
            );
    }
}
