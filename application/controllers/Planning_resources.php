<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_resources extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        require_module('erp');

        $this->load->model('planning_resources_model');
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

        html_vars([
            'page_title' => 'Ressourcen',
            'active_menu' => 'planning',
            'user_display_name' =>
                $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/planning_resources');
    }

    public function search(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            $categories = $this->db
                ->where('is_active', 1)
                ->order_by('sort_order', 'ASC')
                ->order_by('name', 'ASC')
                ->get('planning_resource_categories')
                ->result_array();

            json_response([
                'success' => true,
                'resources' =>
                    $this->planning_resources_model->get_all(),
                'categories' => $categories,
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

            check('resource', 'array');

            $resource_id = $this->planning_resources_model->save(
                request('resource')
            );

            json_response([
                'success' => true,
                'id' => $resource_id,
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

            check('resource_id', 'numeric');

            $this->planning_resources_model->delete(
                (int) request('resource_id')
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
