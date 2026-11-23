<?php defined('BASEPATH') or exit('No direct script access allowed');

class Notifications extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model(
            'notifications_model'
        );

        $this->load->model(
            'planning_impacts_model'
        );
    }

    public function index(): void
    {
        method('get');

        $this->planning_impacts_model
            ->rebuild_all();

        $this->notifications_model
            ->sync_planning_impacts();

        $user_id = (int) session('user_id');

        $range = (string) (
            $this->input->get('range', true)
            ?: 'this_next_week'
        );

        $project_status = (string) (
            $this->input->get(
                'project_status',
                true
            )
            ?: 'all'
        );

        script_vars([
            'notification_user_id' => $user_id,
        ]);

        html_vars([
            'page_title' => 'Dashboard',
            'active_menu' => '',
            'user_display_name' =>
                $this->accounts
                    ->get_user_display_name(
                        $user_id
                    ),
            'dashboard' =>
                $this->get_dashboard_data(
                    $user_id,
                    $range,
                    $project_status
                ),
            'dashboard_filters' => [
                'range' => $range,
                'project_status' =>
                    $project_status,
            ],
            'notifications' =>
                $this->notifications_model
                    ->get_for_user(
                        $user_id,
                        true,
                        500
                    ),
        ]);

        $this->load->view(
            'pages/notifications'
        );
    }

    public function list(): void
    {
        try {
            method('get');

            $this->notifications_model
                ->sync_planning_impacts();

            $user_id =
                (int) session('user_id');

            json_response([
                'success' => true,
                'count' =>
                    $this->notifications_model
                        ->count_due($user_id),
                'notifications' =>
                    $this->notifications_model
                        ->get_for_user(
                            $user_id,
                            false,
                            10
                        ),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function trash(): void
    {
        try {
            method('get');

            $user_id =
                (int) session('user_id');

            json_response([
                'success' => true,
                'notifications' =>
                    $this->notifications_model
                        ->get_deleted_for_user(
                            $user_id
                        ),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function restore(): void
    {
        $this->mutate(
            fn(int $id, int $user_id) =>
                $this->notifications_model
                    ->restore_for_user(
                        $id,
                        $user_id
                    )
        );
    }

    public function history(): void
    {
        try {
            method('get');

            check('recipient_id', 'numeric');

            json_response([
                'success' => true,
                'history' =>
                    $this->notifications_model
                        ->get_history(
                            (int) request(
                                'recipient_id'
                            ),
                            (int) session(
                                'user_id'
                            )
                        ),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function read(): void
    {
        $this->mutate(
            fn(int $id, int $user_id) =>
                $this->notifications_model
                    ->mark_read(
                        $id,
                        $user_id
                    )
        );
    }

    public function delete(): void
    {
        $this->mutate(
            fn(int $id, int $user_id) =>
                $this->notifications_model
                    ->delete_for_user(
                        $id,
                        $user_id
                    )
        );
    }

    public function pin(): void
    {
        try {
            method('post');

            check('recipient_id', 'numeric');
            check('pin', 'boolean');

            $this->notifications_model
                ->pin(
                    (int) request(
                        'recipient_id'
                    ),
                    (int) session('user_id'),
                    filter_var(
                        request('pin'),
                        FILTER_VALIDATE_BOOLEAN
                    )
                );

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function snooze(): void
    {
        try {
            method('post');

            check('recipient_id', 'numeric');
            check('mode', 'string');
            check('until', 'string|null');

            $this->notifications_model
                ->snooze(
                    (int) request(
                        'recipient_id'
                    ),
                    (int) session('user_id'),
                    (string) request('mode'),
                    request('until')
                        ?: null
                );

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function get_dashboard_data(
        int $user_id,
        string $range,
        string $project_status
    ): array {
        $today = date('Y-m-d');

        $allowed_ranges = [
            'this_next_week',
            'month',
            'year',
            'all',
        ];

        if (!in_array($range, $allowed_ranges, true)) {
            $range = 'this_next_week';
        }

        $allowed_project_statuses = [
            'all',
            'planned',
            'active',
            'paused',
        ];

        if (
            !in_array(
                $project_status,
                $allowed_project_statuses,
                true
            )
        ) {
            $project_status = 'all';
        }

        $range_start = $today;
        $range_end = null;

        $range_label = match ($range) {
            'month' => 'Dieser Monat',
            'year' => 'Dieses Jahr',
            'all' => 'Alle zukünftigen Einsätze',
            default => 'Diese und nächste Woche',
        };

        if ($range === 'this_next_week') {
            $range_start = date(
                'Y-m-d',
                strtotime('monday this week')
            );

            $range_end = date(
                'Y-m-d',
                strtotime('sunday next week')
            );
        } elseif ($range === 'month') {
            $range_start = date('Y-m-01');
            $range_end = date('Y-m-t');
        } elseif ($range === 'year') {
            $range_start = date('Y-01-01');
            $range_end = date('Y-12-31');
        }

        $result = [
            'today_count' => 0,
            'week_count' => 0,
            'active_project_count' => 0,
            'open_absence_count' => 0,
            'notification_count' =>
                $this->notifications_model
                    ->count_due($user_id),
            'assignments' => [],
            'projects' => [],
            'matching_assignment_count' => 0,
            'assignments_truncated' => false,
            'range_label' => $range_label,
            'range' => $range,
            'project_status' =>
                $project_status,
        ];

        if (
            !$this->db->table_exists(
                'planning_assignments'
            )
        ) {
            return $result;
        }

        $assignments = $this->db
            ->select(
                'planning_assignments.*,
                 planning_projects.name AS project_name,
                 planning_projects.status AS project_status,
                 customers.first_name AS customer_first_name,
                 customers.last_name AS customer_last_name'
            )
            ->from('planning_assignments')
            ->join(
                'planning_projects',
                'planning_projects.id =
                    planning_assignments.id_planning_projects',
                'inner'
            )
            ->join(
                'users AS customers',
                'customers.id =
                    planning_projects.id_users_customer',
                'left'
            )
            ->where(
                'planning_assignments.status !=',
                'cancelled'
            )
            ->where(
                'planning_assignments.end_datetime >=',
                $range_start . ' 00:00:00'
            );

        if ($range_end !== null) {
            $this->db->where(
                'planning_assignments.start_datetime <=',
                $range_end . ' 23:59:59'
            );
        }

        if ($project_status !== 'all') {
            $this->db->where(
                'planning_projects.status',
                $project_status
            );
        }

        $all_assignments = $this->db
            ->order_by(
                'planning_assignments.start_datetime',
                'ASC'
            )
            ->get()
            ->result_array();

        $result['matching_assignment_count'] =
            count($all_assignments);

        $result['assignments_truncated'] =
            count($all_assignments) > 50;

        $assignments = array_slice(
            $all_assignments,
            0,
            50
        );

        foreach ($assignments as &$assignment) {
            $assignment_id =
                (int) $assignment['id'];

            $assignment['employees'] =
                $this->assignment_relation_names(
                    'planning_assignment_employees',
                    'id_users',
                    'users',
                    $assignment_id,
                    "TRIM(CONCAT_WS(' ', first_name, last_name))"
                );

            $assignment['resources'] =
                $this->assignment_relation_names(
                    'planning_assignment_resources',
                    'id_planning_resources',
                    'planning_resources',
                    $assignment_id,
                    'name'
                );

            $assignment_date = date(
                'Y-m-d',
                strtotime(
                    $assignment['start_datetime']
                )
            );

            if ($assignment_date === $today) {
                $result['today_count']++;
            }
        }

        unset($assignment);

        $result['assignments'] = $assignments;
        $result['week_count'] =
            $result['matching_assignment_count'];

        if (
            $this->db->table_exists(
                'planning_projects'
            )
        ) {
            $result['active_project_count'] =
                $this->db
                    ->where('status', 'active')
                    ->count_all_results(
                        'planning_projects'
                    );

            $result['projects'] = $this->db
                ->select(
                    'planning_projects.id,
                     planning_projects.name,
                     planning_projects.status,
                     planning_projects.start_date,
                     planning_projects.end_date,
                     customers.first_name AS customer_first_name,
                     customers.last_name AS customer_last_name'
                )
                ->from('planning_projects')
                ->join(
                    'users AS customers',
                    'customers.id =
                        planning_projects.id_users_customer',
                    'left'
                )
                ->where_in(
                    'planning_projects.status',
                    ['active', 'planned', 'paused']
                )
                ->order_by(
                    "CASE
                        WHEN " . $this->db->dbprefix(
                            'planning_projects'
                        ) . ".status = 'active' THEN 1
                        WHEN " . $this->db->dbprefix(
                            'planning_projects'
                        ) . ".status = 'paused' THEN 2
                        WHEN " . $this->db->dbprefix(
                            'planning_projects'
                        ) . ".status = 'planned' THEN 3
                        ELSE 4
                     END",
                    '',
                    false
                )
                ->order_by(
                    'planning_projects.start_date',
                    'ASC'
                )
                ->limit(8)
                ->get()
                ->result_array();

            foreach (
                $result['projects'] as &$project
            ) {
                $impact = $this->db
                    ->select(
                        "COUNT(pai.id) AS impact_count,
                         MAX(
                             CASE
                                 WHEN pai.severity = 'critical'
                                     THEN 3
                                 WHEN pai.severity = 'warning'
                                     THEN 2
                                 ELSE 1
                             END
                         ) AS severity_rank",
                        false
                    )
                    ->from(
                        'planning_assignment_impacts AS pai'
                    )
                    ->join(
                        'planning_assignments AS pa',
                        'pa.id =
                            pai.id_planning_assignments',
                        'inner'
                    )
                    ->where(
                        'pa.id_planning_projects',
                        (int) $project['id']
                    )
                    ->where_in(
                        'pai.status',
                        ['open', 'acknowledged']
                    )
                    ->get()
                    ->row_array();

                $project['impact_count'] =
                    (int) (
                        $impact['impact_count'] ?? 0
                    );

                $project['impact_severity'] =
                    match (
                        (int) (
                            $impact['severity_rank'] ?? 0
                        )
                    ) {
                        3 => 'critical',
                        2 => 'warning',
                        1 => 'notice',
                        default => null,
                    };
            }

            unset($project);
        }

        if (
            $this->db->table_exists(
                'employee_absences'
            )
        ) {
            if (can('view', PRIV_USERS)) {
                $result['open_absence_count'] =
                    $this->db
                        ->where('status', 'requested')
                        ->count_all_results(
                            'employee_absences'
                        );
            } elseif (
                $this->db->table_exists(
                    'employee_absence_approvers'
                )
            ) {
                $result['open_absence_count'] =
                    $this->db
                        ->from(
                            'employee_absences AS absences'
                        )
                        ->join(
                            'employee_absence_approvers AS approvers',
                            'approvers.id_users_employee =
                                absences.id_users_employee',
                            'inner'
                        )
                        ->where(
                            'approvers.id_users_approver',
                            $user_id
                        )
                        ->where(
                            'approvers.can_approve',
                            1
                        )
                        ->where(
                            'absences.status',
                            'requested'
                        )
                        ->count_all_results();
            }
        }

        return $result;
    }

    private function assignment_relation_names(
        string $relation_table,
        string $target_column,
        string $target_table,
        int $assignment_id,
        string $name_expression
    ): array {
        if (
            !$this->db->table_exists(
                $relation_table
            ) ||
            !$this->db->table_exists(
                $target_table
            )
        ) {
            return [];
        }

        $rows = $this->db
            ->select(
                $name_expression . ' AS relation_name',
                false
            )
            ->from($relation_table)
            ->join(
                $target_table,
                $target_table . '.id = ' .
                    $relation_table . '.' .
                    $target_column,
                'inner'
            )
            ->where(
                $relation_table .
                    '.id_planning_assignments',
                $assignment_id
            )
            ->order_by(
                'relation_name',
                'ASC'
            )
            ->get()
            ->result_array();

        return array_values(
            array_filter(
                array_map(
                    static fn(array $row): string =>
                        trim(
                            (string) (
                                $row['relation_name'] ??
                                ''
                            )
                        ),
                    $rows
                )
            )
        );
    }

    private function mutate(
        callable $callback
    ): void {
        try {
            method('post');

            check('recipient_id', 'numeric');

            $callback(
                (int) request('recipient_id'),
                (int) session('user_id')
            );

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
