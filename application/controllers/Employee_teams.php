<?php defined('BASEPATH') or exit('No direct script access allowed');

class Employee_teams extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('employee_teams_model');
        $this->load->model('providers_model');

        $this->load->library('accounts');
    }

    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_USERS)) {
            redirect('login');

            return;
        }

        $user_id = (int) session('user_id');

        $employees = filter_sensitive_users_data(
            $this->providers_model->get()
        );

        html_vars([
            'page_title' => 'Teams',
            'active_menu' => PRIV_USERS,
            'user_display_name' =>
                $this->accounts->get_user_display_name($user_id),
            'employees' => $employees,
        ]);

        $this->load->view('pages/employee_teams');
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
                'teams' => $this->employee_teams_model->get_all(),
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

            check('team', 'array');

            $team_id = $this->employee_teams_model->save(
                request('team')
            );

            json_response([
                'success' => true,
                'id' => $team_id,
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

            check('team_id', 'numeric');

            $this->employee_teams_model->delete(
                (int) request('team_id')
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
