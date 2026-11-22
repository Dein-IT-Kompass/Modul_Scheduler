<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointment_series extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointment_series_model');
        $this->load->model('appointments_model');
    }

    public function create(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('series_data', 'array');
            $series = request('series_data');

            $result = $this->appointment_series_model->create_with_appointments($series);

            json_response([
                'success' => true,
                'series_id' => $result['series_id'],
                'appointment_ids' => $result['appointment_ids'],
                'occurrence_count' => count($result['appointment_ids']),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function preview(): void
    {
        try {
            method('post');
            check('series_data', 'array');
            $series = request('series_data');
            $this->appointment_series_model->validate($series);

            json_response([
                'success' => true,
                'dates' => $this->appointment_series_model->generate_occurrence_dates($series),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
