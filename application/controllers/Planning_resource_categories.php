<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_resource_categories extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        require_module('erp');

        $this->load->model(
            'planning_resource_categories_model'
        );

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
            'page_title' => 'Ressourcenkategorien',
            'active_menu' => 'planning',
            'user_display_name' =>
                $this->accounts->get_user_display_name(
                    $user_id
                ),
        ]);

        $this->load->view(
            'pages/planning_resource_categories'
        );
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
                'categories' =>
                    $this->planning_resource_categories_model
                        ->get_all(),
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

            check('category', 'array');

            $category_id =
                $this->planning_resource_categories_model
                    ->save(request('category'));

            json_response([
                'success' => true,
                'id' => $category_id,
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

            check('category_id', 'numeric');

            $this->planning_resource_categories_model
                ->delete(
                    (int) request('category_id')
                );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
