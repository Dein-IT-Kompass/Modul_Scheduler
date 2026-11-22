<?php defined('BASEPATH') or exit('No direct script access allowed');

class Series_planner extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointment_series_model');
    }

    public function catalog(): void
    {
        try {
            method('get');
            if (cannot('view', PRIV_APPOINTMENTS)) abort(403, 'Forbidden');
            $categories = $this->db->select('id, name')->from('service_categories')->order_by('name')->get()->result_array();
            $services = $this->db
                ->select('id, name, duration, attendants_number, location, color, id_service_categories')
                ->from('services')
                ->order_by('name')
                ->get()
                ->result_array();

            $providers = $this->db
                ->select('users.id, users.first_name, users.last_name')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles')
                ->where('roles.slug', DB_SLUG_PROVIDER)
                ->order_by('users.last_name')
                ->get()
                ->result_array();
            $assignments = $this->db->select('id_services, id_users')->get('services_providers')->result_array();
            json_response(compact('categories', 'services', 'providers', 'assignments'));
        } catch (Throwable $e) { json_exception($e); }
    }

    public function load(string $series_id): void
    {
        try {
            method('get');
            if (cannot('view', PRIV_APPOINTMENTS)) abort(403, 'Forbidden');
            $series = $this->appointment_series_model->get_for_edit((int)$series_id);
            if (!$series) abort(404, 'Kursserie nicht gefunden.');
            json_response(['success' => true, 'series' => $series]);
        } catch (Throwable $e) { json_exception($e); }
    }

    public function preview(): void
    {
        try {
            method('post');
            if (cannot('add', PRIV_APPOINTMENTS) && cannot('edit', PRIV_APPOINTMENTS)) abort(403, 'Forbidden');
            check('series_data', 'array');
            $series = request('series_data');
            json_response(['success' => true, 'occurrences' => $this->appointment_series_model->preview($series)]);
        } catch (Throwable $e) { json_exception($e); }
    }

    public function create(): void
    {
        try {
            method('post');
            if (cannot('add', PRIV_APPOINTMENTS)) abort(403, 'Forbidden');
            check('series_data', 'array');
            $result = $this->appointment_series_model->create_with_appointments(request('series_data'));
            json_response(['success'=>true,'series_id'=>$result['series_id'],'appointment_ids'=>$result['appointment_ids'],'occurrence_count'=>count($result['appointment_ids'])]);
        } catch (Throwable $e) { json_exception($e); }
    }

    public function update(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_APPOINTMENTS)) abort(403, 'Forbidden');
            check('series_id', 'numeric');
            check('series_data', 'array');
            $result = $this->appointment_series_model->update_with_future_appointments((int)request('series_id'), request('series_data'));
            json_response(['success'=>true] + $result);
        } catch (Throwable $e) { json_exception($e); }
    }
}
