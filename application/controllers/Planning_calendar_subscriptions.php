<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_calendar_subscriptions extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model(
            'planning_calendar_subscriptions_model'
        );
    }

    public function status(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('employee_id', 'numeric');

            json_response(
                $this->planning_calendar_subscriptions_model
                    ->status_for_employee(
                        (int) request('employee_id')
                    )
            );
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function create(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('employee_id', 'numeric');

            $result =
                $this->planning_calendar_subscriptions_model
                    ->create_for_employee(
                        (int) request('employee_id')
                    );

            json_response(
                $this->append_urls($result)
            );
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function rotate(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('employee_id', 'numeric');

            $result =
                $this->planning_calendar_subscriptions_model
                    ->rotate_for_employee(
                        (int) request('employee_id')
                    );

            json_response(
                $this->append_urls($result)
            );
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function disable(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('employee_id', 'numeric');

            $this->planning_calendar_subscriptions_model
                ->disable_for_employee(
                    (int) request('employee_id')
                );

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function append_urls(array $result): array
    {
        $https_url = site_url(
            'calendar_feed/ics/' . $result['token']
        );

        $webcal_url = preg_replace(
            '#^https?://#',
            'webcal://',
            $https_url
        );

        unset($result['token']);

        $result['success'] = true;
        $result['https_url'] = $https_url;
        $result['webcal_url'] = $webcal_url;

        return $result;
    }
}
