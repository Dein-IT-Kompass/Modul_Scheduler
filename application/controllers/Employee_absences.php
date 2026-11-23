<?php defined('BASEPATH') or exit('No direct script access allowed');

class Employee_absences extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('employee_absences_model');
        $this->load->model(
            'employee_absence_approvers_model'
        );
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

        $manageableEmployeeIds = $canManageAll
            ? []
            : $this->employee_absence_approvers_model
                ->get_manageable_employee_ids($userId);

        $employees = $canManageAll
            ? filter_sensitive_users_data(
                $this->providers_model->get()
            )
            : $this->get_manageable_employees(
                $manageableEmployeeIds
            );

        $canManageAbsences =
            $canManageAll ||
            !empty($manageableEmployeeIds);

        html_vars([
            'page_title' => 'Abwesenheiten',
            'active_menu' => PRIV_USERS,
            'user_display_name' =>
                $this->accounts
                    ->get_user_display_name($userId),
            'can_manage_absences' =>
                $canManageAbsences,
            'employees' => $employees,
            'current_user_id' => $userId,
        ]);

        script_vars([
            'current_user_id' => $userId,
            'can_manage_absences' =>
                $canManageAbsences,
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

            if (can('view', PRIV_USERS)) {
                $records =
                    $this->employee_absences_model
                        ->get_all();
            } else {
                $employeeIds =
                    $this->employee_absence_approvers_model
                        ->get_manageable_employee_ids(
                            $userId
                        );

                $employeeIds[] = $userId;

                $records =
                    $this->employee_absences_model
                        ->get_for_user_ids(
                            $employeeIds
                        );
            }

            foreach ($records as &$record) {
                $record['can_decide'] =
                    $this->can_decide_absence(
                        $userId,
                        (int) $record[
                            'id_users_employee'
                        ]
                    );
            }

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

            $employeeId = (int) (
                $absence['id_users_employee'] ?? 0
            );

            if ($employeeId < 1) {
                $employeeId = $userId;
            }

            $isOwnAbsence = $employeeId === $userId;
            $type = (string) (
                $absence['absence_type'] ?? ''
            );

            if (
                !$isOwnAbsence &&
                !$this->can_create_for_employee(
                    $userId,
                    $employeeId
                )
            ) {
                abort(
                    403,
                    'Für diesen Mitarbeiter darf keine ' .
                    'Abwesenheit eingetragen werden.'
                );
            }

            $absence['id_users_employee'] =
                $employeeId;

            $mode =
                $this->employee_absence_approvers_model
                    ->get_approval_mode($employeeId);

            if (
                $isOwnAbsence &&
                $type !== 'sick' &&
                $mode === 'supervisor_only'
            ) {
                abort(
                    403,
                    'Diese Abwesenheit kann nur durch ' .
                    'einen berechtigten Vorgesetzten ' .
                    'eingetragen werden.'
                );
            }

            $createApproved = false;

            if ($type !== 'sick') {
                if (
                    $isOwnAbsence &&
                    $mode === 'self_approved'
                ) {
                    $createApproved = true;
                }

                if (
                    !$isOwnAbsence &&
                    $this->can_create_approved(
                        $userId,
                        $employeeId
                    )
                ) {
                    $createApproved = true;
                }
            }

            $id = $this->employee_absences_model
                ->create(
                    $absence,
                    $userId,
                    $createApproved
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

            if (!$userId) {
                abort(401, 'Unauthorized');
            }

            $absence =
                $this->employee_absences_model->find(
                    (int) request('absence_id')
                );

            if (!$absence) {
                abort(
                    404,
                    'Abwesenheit nicht gefunden.'
                );
            }

            $canManage =
                can('edit', PRIV_USERS) ||
                $this->can_decide_absence(
                    $userId,
                    (int) $absence[
                        'id_users_employee'
                    ]
                );

            $this->employee_absences_model->cancel(
                (int) request('absence_id'),
                $userId,
                $canManage
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

            $userId = (int) session('user_id');

            if (!$userId) {
                abort(401, 'Unauthorized');
            }

            check('absence_id', 'numeric');
            check('decision_note', 'string|null');

            $absence =
                $this->employee_absences_model->find(
                    (int) request('absence_id')
                );

            if (!$absence) {
                abort(
                    404,
                    'Abwesenheitsantrag nicht gefunden.'
                );
            }

            if (
                !$this->can_decide_absence(
                    $userId,
                    (int) $absence[
                        'id_users_employee'
                    ]
                )
            ) {
                abort(
                    403,
                    'Dieser Abwesenheitsantrag darf ' .
                    'nicht bearbeitet werden.'
                );
            }

            $this->employee_absences_model->decide(
                (int) request('absence_id'),
                $status,
                $userId,
                request('decision_note', '')
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function can_decide_absence(
        int $userId,
        int $employeeId
    ): bool {
        if (can('edit', PRIV_USERS)) {
            return true;
        }

        return $this->employee_absence_approvers_model
            ->can_approve($userId, $employeeId);
    }

    private function can_create_for_employee(
        int $userId,
        int $employeeId
    ): bool {
        if (can('add', PRIV_USERS)) {
            return true;
        }

        return $this->employee_absence_approvers_model
            ->can_create_approved(
                $userId,
                $employeeId
            );
    }

    private function can_create_approved(
        int $userId,
        int $employeeId
    ): bool {
        if (can('add', PRIV_USERS)) {
            return true;
        }

        return $this->employee_absence_approvers_model
            ->can_create_approved(
                $userId,
                $employeeId
            );
    }

    private function get_manageable_employees(
        array $employeeIds
    ): array {
        if (empty($employeeIds)) {
            return [];
        }

        $employeeIds = array_map('intval', $employeeIds);

        $employees = $this->providers_model->get();

        $employees = array_values(
            array_filter(
                $employees,
                static fn(array $employee): bool =>
                    in_array(
                        (int) $employee['id'],
                        $employeeIds,
                        true
                    )
            )
        );

        return filter_sensitive_users_data($employees);
    }
}
