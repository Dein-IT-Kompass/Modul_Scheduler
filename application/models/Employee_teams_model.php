<?php defined('BASEPATH') or exit('No direct script access allowed');

class Employee_teams_model extends EA_Model
{
    public function get_all(): array
    {
        $teams = $this->db
            ->order_by('name', 'ASC')
            ->get('employee_teams')
            ->result_array();

        foreach ($teams as &$team) {
            $team['members'] = $this->get_members((int) $team['id']);
        }

        unset($team);

        return $teams;
    }

    public function find(int $team_id): ?array
    {
        $team = $this->db
            ->get_where('employee_teams', ['id' => $team_id])
            ->row_array();

        if (!$team) {
            return null;
        }

        $team['members'] = $this->get_members($team_id);

        return $team;
    }

    public function get_members(int $team_id): array
    {
        return $this->db
            ->select(
                'employee_team_members.*,
                 users.first_name,
                 users.last_name,
                 users.email'
            )
            ->from('employee_team_members')
            ->join(
                'users',
                'users.id = employee_team_members.id_users',
                'inner'
            )
            ->where('employee_team_members.id_employee_teams', $team_id)
            ->order_by('users.last_name', 'ASC')
            ->order_by('users.first_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function save(array $team): int
    {
        if (empty(trim((string) ($team['name'] ?? '')))) {
            throw new InvalidArgumentException('Der Teamname ist erforderlich.');
        }

        $now = date('Y-m-d H:i:s');

        $record = [
            'name' => trim((string) $team['name']),
            'description' => trim((string) ($team['description'] ?? '')),
            'color' => $team['color'] ?? '#7cbae8',
            'is_active' => !empty($team['is_active']) ? 1 : 0,
            'update_datetime' => $now,
        ];

        $this->db->trans_begin();

        try {
            if (!empty($team['id'])) {
                $team_id = (int) $team['id'];

                if (!$this->find($team_id)) {
                    throw new RuntimeException('Team nicht gefunden.');
                }

                $this->db
                    ->where('id', $team_id)
                    ->update('employee_teams', $record);
            } else {
                $record['create_datetime'] = $now;

                $this->db->insert('employee_teams', $record);

                $team_id = (int) $this->db->insert_id();
            }

            $this->save_members(
                $team_id,
                $team['members'] ?? [],
                $now
            );

            if ($this->db->trans_status() === false) {
                throw new RuntimeException(
                    'Team konnte nicht vollständig gespeichert werden.'
                );
            }

            $this->db->trans_commit();

            return $team_id;
        } catch (Throwable $e) {
            $this->db->trans_rollback();

            throw $e;
        }
    }

    public function delete(int $team_id): void
    {
        if (!$this->find($team_id)) {
            throw new RuntimeException('Team nicht gefunden.');
        }

        $this->db->delete('employee_teams', ['id' => $team_id]);
    }

    public function can_approve_absence(
        int $manager_user_id,
        int $employee_user_id
    ): bool {
        return $this->db
            ->select('manager.id')
            ->from('employee_team_members AS manager')
            ->join(
                'employee_team_members AS employee',
                'employee.id_employee_teams = manager.id_employee_teams',
                'inner'
            )
            ->where('manager.id_users', $manager_user_id)
            ->where('manager.can_approve_absences', 1)
            ->where('employee.id_users', $employee_user_id)
            ->limit(1)
            ->get()
            ->num_rows() > 0;
    }

    private function save_members(
        int $team_id,
        array $members,
        string $now
    ): void {
        $this->db->delete(
            'employee_team_members',
            ['id_employee_teams' => $team_id]
        );

        $seen = [];

        foreach ($members as $member) {
            $user_id = (int) ($member['id_users'] ?? 0);

            if ($user_id < 1 || isset($seen[$user_id])) {
                continue;
            }

            $seen[$user_id] = true;

            $role = $member['team_role'] ?? 'member';

            if (!in_array($role, ['member', 'lead', 'planner'], true)) {
                $role = 'member';
            }

            $this->db->insert('employee_team_members', [
                'id_employee_teams' => $team_id,
                'id_users' => $user_id,
                'team_role' => $role,
                'can_approve_absences' =>
                    !empty($member['can_approve_absences']) ? 1 : 0,
                'can_manage_calendar' =>
                    !empty($member['can_manage_calendar']) ? 1 : 0,
                'can_manage_series' =>
                    !empty($member['can_manage_series']) ? 1 : 0,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]);
        }
    }
}
