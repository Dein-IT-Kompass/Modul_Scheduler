<?php defined('BASEPATH') or exit('No direct script access allowed');

class Employee_absence_approvers_model extends EA_Model
{
    public function can_approve(
        int $approver_user_id,
        int $employee_user_id
    ): bool {
        if ($approver_user_id < 1 || $employee_user_id < 1) {
            return false;
        }

        if ($this->has_individual_permission(
            $approver_user_id,
            $employee_user_id,
            'can_approve'
        )) {
            return true;
        }

        $this->load->model('employee_teams_model');

        return $this->employee_teams_model->can_approve_absence(
            $approver_user_id,
            $employee_user_id
        );
    }

    public function can_create_approved(
        int $approver_user_id,
        int $employee_user_id
    ): bool {
        if ($approver_user_id < 1 || $employee_user_id < 1) {
            return false;
        }

        return $this->has_individual_permission(
            $approver_user_id,
            $employee_user_id,
            'can_create_approved'
        );
    }

    public function get_manageable_employee_ids(
        int $approver_user_id
    ): array {
        if ($approver_user_id < 1) {
            return [];
        }

        $ids = [];

        if ($this->db->table_exists(
            'employee_absence_approvers'
        )) {
            $rows = $this->db
                ->select('id_users_employee')
                ->from('employee_absence_approvers')
                ->where('id_users_approver', $approver_user_id)
                ->where('can_approve', 1)
                ->get()
                ->result_array();

            foreach ($rows as $row) {
                $ids[] = (int) $row['id_users_employee'];
            }
        }

        if (
            $this->db->table_exists('employee_team_members') &&
            $this->db->table_exists('employee_teams')
        ) {
            $rows = $this->db
                ->distinct()
                ->select('employee.id_users')
                ->from('employee_team_members AS manager')
                ->join(
                    'employee_team_members AS employee',
                    'employee.id_employee_teams = ' .
                    'manager.id_employee_teams',
                    'inner'
                )
                ->join(
                    'employee_teams',
                    'employee_teams.id = ' .
                    'manager.id_employee_teams',
                    'inner'
                )
                ->where(
                    'manager.id_users',
                    $approver_user_id
                )
                ->where(
                    'manager.can_approve_absences',
                    1
                )
                ->where('employee_teams.is_active', 1)
                ->get()
                ->result_array();

            foreach ($rows as $row) {
                $ids[] = (int) $row['id_users'];
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return $ids;
    }

    public function get_approval_mode(
        int $employee_user_id
    ): string {
        $mode = $this->db
            ->select('absence_approval_mode')
            ->from('users')
            ->where('id', $employee_user_id)
            ->get()
            ->row('absence_approval_mode');

        $allowed = [
            'approval_required',
            'self_approved',
            'supervisor_only',
        ];

        return in_array($mode, $allowed, true)
            ? $mode
            : 'approval_required';
    }

    public function get_rules(
        int $employee_user_id
    ): array {
        if (
            $employee_user_id < 1 ||
            !$this->db->table_exists(
                'employee_absence_approvers'
            )
        ) {
            return [];
        }

        return $this->db
            ->select(
                'employee_absence_approvers.*,
                 approvers.first_name,
                 approvers.last_name,
                 approvers.email,
                 roles.slug AS role_slug'
            )
            ->from('employee_absence_approvers')
            ->join(
                'users AS approvers',
                'approvers.id = ' .
                'employee_absence_approvers.id_users_approver',
                'inner'
            )
            ->join(
                'roles',
                'roles.id = approvers.id_roles',
                'left'
            )
            ->where(
                'employee_absence_approvers.id_users_employee',
                $employee_user_id
            )
            ->order_by('approvers.first_name', 'ASC')
            ->order_by('approvers.last_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_candidates(
        int $employee_user_id
    ): array {
        if ($employee_user_id < 1) {
            return [];
        }

        return $this->db
            ->select(
                'users.id,
                 users.first_name,
                 users.last_name,
                 users.email,
                 roles.slug AS role_slug'
            )
            ->from('users')
            ->join(
                'roles',
                'roles.id = users.id_roles',
                'left'
            )
            ->where('users.id !=', $employee_user_id)
            ->where_not_in(
                'roles.slug',
                ['customer']
            )
            ->order_by('users.first_name', 'ASC')
            ->order_by('users.last_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function save_rules(
        int $employee_user_id,
        array $rules
    ): void {
        if ($employee_user_id < 1) {
            throw new InvalidArgumentException(
                'Ungültiger Mitarbeiter.'
            );
        }

        $employeeExists = $this->db
            ->where('id', $employee_user_id)
            ->count_all_results('users') > 0;

        if (!$employeeExists) {
            throw new RuntimeException(
                'Mitarbeiter wurde nicht gefunden.'
            );
        }

        $now = date('Y-m-d H:i:s');

        $this->db->trans_begin();

        try {
            $this->db->delete(
                'employee_absence_approvers',
                [
                    'id_users_employee' =>
                        $employee_user_id,
                ]
            );

            $seen = [];

            foreach ($rules as $rule) {
                $approverId = (int) (
                    $rule['id_users_approver'] ?? 0
                );

                if (
                    $approverId < 1 ||
                    $approverId === $employee_user_id ||
                    isset($seen[$approverId])
                ) {
                    continue;
                }

                $canApprove = !empty(
                    $rule['can_approve']
                );

                $canCreateApproved = !empty(
                    $rule['can_create_approved']
                );

                if (
                    !$canApprove &&
                    !$canCreateApproved
                ) {
                    continue;
                }

                $approverExists = $this->db
                    ->where('id', $approverId)
                    ->count_all_results('users') > 0;

                if (!$approverExists) {
                    continue;
                }

                $seen[$approverId] = true;

                $this->db->insert(
                    'employee_absence_approvers',
                    [
                        'id_users_employee' =>
                            $employee_user_id,
                        'id_users_approver' =>
                            $approverId,
                        'can_approve' =>
                            $canApprove ? 1 : 0,
                        'can_create_approved' =>
                            $canCreateApproved ? 1 : 0,
                        'create_datetime' => $now,
                        'update_datetime' => $now,
                    ]
                );
            }

            if ($this->db->trans_status() === false) {
                throw new RuntimeException(
                    'Genehmiger konnten nicht vollständig ' .
                    'gespeichert werden.'
                );
            }

            $this->db->trans_commit();
        } catch (Throwable $e) {
            $this->db->trans_rollback();

            throw $e;
        }
    }

    private function has_individual_permission(
        int $approver_user_id,
        int $employee_user_id,
        string $permission
    ): bool {
        if (!$this->db->table_exists(
            'employee_absence_approvers'
        )) {
            return false;
        }

        if (!in_array(
            $permission,
            ['can_approve', 'can_create_approved'],
            true
        )) {
            return false;
        }

        return $this->db
            ->from('employee_absence_approvers')
            ->where('id_users_employee', $employee_user_id)
            ->where('id_users_approver', $approver_user_id)
            ->where($permission, 1)
            ->limit(1)
            ->count_all_results() > 0;
    }
}
