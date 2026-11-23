<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_resources extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        require_module('erp');

        $this->load->model('planning_resources_model');
        $this->load->model(
            'planning_resource_unavailabilities_model'
        );
        $this->load->model('planning_impacts_model');
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

            $resources =
                $this->planning_resources_model->get_all();

            foreach ($resources as &$resource) {
                $resource['availability'] =
                    $this->get_resource_availability(
                        (int) $resource['id']
                    );
            }

            unset($resource);

            json_response([
                'success' => true,
                'resources' => $resources,
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

    private function get_resource_availability(
        int $resource_id
    ): array {
        $now = date('Y-m-d H:i:s');

        $warning_until = date(
            'Y-m-d H:i:s',
            strtotime('+7 days')
        );

        $current = $this->db
            ->from(
                'planning_resource_unavailabilities'
            )
            ->where(
                'id_planning_resources',
                $resource_id
            )
            ->where('status', 'active')
            ->where('start_datetime <=', $now)
            ->group_start()
            ->where('end_datetime >=', $now)
            ->or_where(
                'end_datetime IS NULL',
                null,
                false
            )
            ->group_end()
            ->order_by('start_datetime', 'ASC')
            ->limit(1)
            ->get()
            ->row_array();

        if ($current) {
            return [
                'state' => 'unavailable',
                'label' => 'Nicht verfügbar',
                'type' => $current['type'],
                'start_datetime' =>
                    $current['start_datetime'],
                'end_datetime' =>
                    $current['end_datetime'],
                'title' => $current['title'],
            ];
        }

        $upcoming = $this->db
            ->from(
                'planning_resource_unavailabilities'
            )
            ->where(
                'id_planning_resources',
                $resource_id
            )
            ->where('status', 'active')
            ->where('start_datetime >', $now)
            ->where(
                'start_datetime <=',
                $warning_until
            )
            ->order_by('start_datetime', 'ASC')
            ->limit(1)
            ->get()
            ->row_array();

        if ($upcoming) {
            return [
                'state' => 'upcoming',
                'label' => 'Bald nicht verfügbar',
                'type' => $upcoming['type'],
                'start_datetime' =>
                    $upcoming['start_datetime'],
                'end_datetime' =>
                    $upcoming['end_datetime'],
                'title' => $upcoming['title'],
            ];
        }

        return [
            'state' => 'available',
            'label' => 'Verfügbar',
            'type' => null,
            'start_datetime' => null,
            'end_datetime' => null,
            'title' => null,
        ];
    }

    public function unavailabilities(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('resource_id', 'numeric');

            json_response([
                'success' => true,
                'records' =>
                    $this
                        ->planning_resource_unavailabilities_model
                        ->get_by_resource(
                            (int) request('resource_id')
                        ),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function store_unavailability(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('unavailability', 'array');

            $unavailability =
                request('unavailability');

            $id =
                $this
                    ->planning_resource_unavailabilities_model
                    ->save(
                        $unavailability,
                        (int) session('user_id')
                    );

            $this->planning_impacts_model
                ->rebuild_all();

            json_response([
                'success' => true,
                'id' => $id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function resolve_unavailability(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('unavailability_id', 'numeric');
            check('resource_id', 'numeric');

            $this
                ->planning_resource_unavailabilities_model
                ->resolve(
                    (int) request(
                        'unavailability_id'
                    ),
                    (int) request('resource_id')
                );

            $this->planning_impacts_model
                ->rebuild_all();

            json_response([
                'success' => true,
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
