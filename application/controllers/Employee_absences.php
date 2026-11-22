<?php defined('BASEPATH') or exit('No direct script access allowed');

class Employee_absences extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('employee_absences_model');
        $this->load->model('providers_model');
        $this->load->library('accounts');
    }

    public function index(): void
    {
        method('get');

        $userId = (int) session('user_id');

        if (!$userId) {
            redirect('login');

            return;
        }

        $canManageAll = can('view', PRIV_USERS);

        $employees = $canManageAll
            ? filter_sensitive_users_data($this->providers_model->get())
            : [];

        html_vars([
            'page_title' => 'Abwesenheiten',
            'active_menu' => PRIV_USERS,
            'user_display_name' => $this->accounts->get_user_display_name($userId),
            'can_manage_absences' => $canManageAll,
            'employees' => $employees,
            'current_user_id' => $userId,
        ]);

        script_vars([
            'current_user_id' => $userId,
            'can_manage_absences' => $canManageAll,
        ]);

        $this->load->view('pages/employee_absences');
    }

    public function search(): void
    {
        try {
            method('get');

            $userId = (int) session('user_id');

            if (!$userId) {
                abort(401, 'Unauthorized');
            }

            $records = can('view', PRIV_USERS)
                ? $this->employee_absences_model->get_all()
                : $this->employee_absences_model->get_for_user($userId);

            json_response([
                'success' => true,
                'records' => $records,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function store(): void
    {
        try {
            method('post');
            check('absence', 'array');

            $userId = (int) session('user_id');

            if (!$userId) {
                abort(401, 'Unauthorized');
            }

            $absence = request('absence');

            if (!can('add', PRIV_USERS)) {
                $absence['id_users_employee'] = $userId;
            }

            $id = $this->employee_absences_model->create(
                $absence,
                $userId
            );

            json_response([
                'success' => true,
                'id' => $id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function approve(): void
    {
        $this->decide('approved');
    }

    public function reject(): void
    {
        $this->decide('rejected');
    }

    public function cancel(): void
    {
        try {
            method('post');
            check('absence_id', 'numeric');

            $userId = (int) session('user_id');

            $this->employee_absences_model->cancel(
                (int) request('absence_id'),
                $userId,
                can('edit', PRIV_USERS)
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function decide(string $status): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('absence_id', 'numeric');
            check('decision_note', 'string|null');

            $this->employee_absences_model->decide(
                (int) request('absence_id'),
                $status,
                (int) session('user_id'),
                request('decision_note', '')
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
