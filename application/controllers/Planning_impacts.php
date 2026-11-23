<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_impacts extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!module_enabled('erp')) {
            abort(404);
        }

        $this->load->model(
            'planning_impacts_model'
        );
    }

    public function rebuild(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            $result =
                $this->planning_impacts_model
                    ->rebuild_all();

            json_response([
                'success' => true,
                'detected' => $result['detected'],
                'resolved' => $result['resolved'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function project(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('project_id', 'numeric');

            $project_id =
                (int) request('project_id');

            $this->planning_impacts_model
                ->rebuild_for_project($project_id);

            json_response([
                'success' => true,
                'impacts' =>
                    $this->planning_impacts_model
                        ->get_open_by_project(
                            $project_id
                        ),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function assignment(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('assignment_id', 'numeric');

            $assignment_id =
                (int) request('assignment_id');

            $this->planning_impacts_model
                ->rebuild_for_assignment(
                    $assignment_id
                );

            json_response([
                'success' => true,
                'impacts' =>
                    $this->planning_impacts_model
                        ->get_by_assignment(
                            $assignment_id
                        ),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function decide(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('impact_id', 'numeric');
            check('decision', 'string');
            check('notes', 'string|null');

            $this->planning_impacts_model
                ->decide(
                    (int) request('impact_id'),
                    (string) request('decision'),
                    (string) (
                        request('notes') ?? ''
                    )
                );

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
