<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_projects extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        require_module('erp');

        $this->load->model('planning_projects_model');
        $this->load->model('planning_project_phases_model');
        $this->load->model('planning_assignments_model');
        $this->load->model('planning_impacts_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');

        $this->load->library('accounts');
    }

    public function index(): void
    {
        method('get');

        $user_id = (int) session('user_id');

        if (!$user_id) {
            redirect('login');
            return;
        }

        if (cannot('view', PRIV_USERS)) {
            abort(403, 'Forbidden');
        }

        $employees = filter_sensitive_users_data(
            $this->providers_model->get()
        );

        $customers = $this->db
            ->select(
                'users.id,
                 users.first_name,
                 users.last_name,
                 users.email,
                 users.phone_number,
                 users.mobile_number,
                 users.address,
                 users.zip_code,
                 users.city'
            )
            ->from('users')
            ->join(
                'roles',
                'roles.id = users.id_roles',
                'inner'
            )
            ->where('roles.slug', 'customer')
            ->order_by('users.last_name', 'ASC')
            ->order_by('users.first_name', 'ASC')
            ->get()
            ->result_array();

        $teams = $this->db
            ->where('is_active', 1)
            ->order_by('name', 'ASC')
            ->get('employee_teams')
            ->result_array();

        $resources = $this->db
            ->select(
                'planning_resources.*,
                 planning_resource_categories.name AS category_name'
            )
            ->from('planning_resources')
            ->join(
                'planning_resource_categories',
                'planning_resource_categories.id =
                    planning_resources.id_planning_resource_categories',
                'left'
            )
            ->where('planning_resources.is_active', 1)
            ->order_by(
                'planning_resource_categories.name',
                'ASC'
            )
            ->order_by('planning_resources.name', 'ASC')
            ->get()
            ->result_array();

        $services = $this->services_model->get();

        html_vars([
            'page_title' => 'Projekte / Einsätze',
            'active_menu' => 'planning',
            'user_display_name' =>
                $this->accounts->get_user_display_name($user_id),
            'employees' => $employees,
            'customers' => $customers,
            'teams' => $teams,
            'resources' => $resources,
            'services' => $services,
        ]);

        $this->load->view('pages/planning_projects');
    }

    public function search(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            json_response([
                'success' => true,
                'projects' =>
                    $this->planning_projects_model->get_all(),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function store(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('project', 'array');

            $project_id =
                $this->planning_projects_model->save(
                    request('project')
                );

            json_response([
                'success' => true,
                'id' => $project_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function phases(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('project_id', 'numeric');

            $project_id = (int) request('project_id');

            $project = $this->planning_projects_model
                ->find($project_id);

            if (!$project) {
                throw new RuntimeException(
                    'Projekt nicht gefunden.'
                );
            }

            $this->planning_impacts_model
                ->rebuild_for_project($project_id);

            json_response([
                'success' => true,
                'project' => $project,
                'phases' =>
                    $this->planning_project_phases_model
                        ->get_by_project($project_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function store_phase(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('phase', 'array');

            $phase_id =
                $this->planning_project_phases_model
                    ->save(request('phase'));

            json_response([
                'success' => true,
                'id' => $phase_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function delete_phase(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('phase_id', 'numeric');

            $this->planning_project_phases_model
                ->delete(
                    (int) request('phase_id')
                );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function assignments(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('project_id', 'numeric');

            $project_id =
                (int) request('project_id');

            $project =
                $this->planning_projects_model
                    ->find($project_id);

            if (!$project) {
                throw new RuntimeException(
                    'Projekt nicht gefunden.'
                );
            }

            json_response([
                'success' => true,
                'project' => $project,
                'assignments' =>
                    $this->planning_assignments_model
                        ->get_by_project($project_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function store_assignment(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('assignment', 'array');

            $assignment_id =
                $this->planning_assignments_model
                    ->save(
                        request('assignment')
                    );

            json_response([
                'success' => true,
                'id' => $assignment_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function delete_assignment(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('assignment_id', 'numeric');

            $this->planning_assignments_model
                ->delete(
                    (int) request('assignment_id')
                );

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function acknowledge_impacts(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('project_id', 'numeric');

            $project_id =
                (int) request('project_id');

            $impact_ids = $this->db
                ->select(
                    'planning_assignment_impacts.id'
                )
                ->from(
                    'planning_assignment_impacts'
                )
                ->join(
                    'planning_assignments',
                    'planning_assignments.id =
                        planning_assignment_impacts.id_planning_assignments',
                    'inner'
                )
                ->where(
                    'planning_assignments.id_planning_projects',
                    $project_id
                )
                ->where(
                    'planning_assignment_impacts.status',
                    'open'
                )
                ->get()
                ->result_array();

            if ($impact_ids) {
                $this->db
                    ->where_in(
                        'id',
                        array_column(
                            $impact_ids,
                            'id'
                        )
                    )
                    ->update(
                        'planning_assignment_impacts',
                        [
                            'status' =>
                                'acknowledged',
                            'decision' =>
                                'continue',
                            'decision_notes' =>
                                'Warnung wurde zur Kenntnis genommen.',
                            'id_users_decided_by' =>
                                (int) session('user_id'),
                            'update_datetime' =>
                                date('Y-m-d H:i:s'),
                        ]
                    );
            }

            json_response([
                'success' => true,
                'acknowledged' =>
                    count($impact_ids),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function delete(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('project_id', 'numeric');

            $this->planning_projects_model->delete(
                (int) request('project_id')
            );

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
