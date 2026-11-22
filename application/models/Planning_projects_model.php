<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_projects_model extends EA_Model
{
    private const ALLOWED_STATUSES = [
        'planned',
        'active',
        'paused',
        'completed',
        'cancelled',
    ];

    public function get_all(): array
    {
        $projects = $this->db
            ->select(
                'planning_projects.*,
                 lead.first_name AS lead_first_name,
                 lead.last_name AS lead_last_name'
            )
            ->from('planning_projects')
            ->join(
                'users AS lead',
                'lead.id = planning_projects.id_users_project_lead',
                'left'
            )
            ->order_by('planning_projects.start_date', 'DESC')
            ->order_by('planning_projects.id', 'DESC')
            ->order_by('planning_projects.name', 'ASC')
            ->get()
            ->result_array();

        foreach ($projects as &$project) {
            $this->append_relations($project);
            $this->append_counts($project);
        }

        unset($project);

        return $projects;
    }

    public function find(int $project_id): ?array
    {
        $project = $this->db
            ->select(
                'planning_projects.*,
                 lead.first_name AS lead_first_name,
                 lead.last_name AS lead_last_name'
            )
            ->from('planning_projects')
            ->join(
                'users AS lead',
                'lead.id = planning_projects.id_users_project_lead',
                'left'
            )
            ->where('planning_projects.id', $project_id)
            ->get()
            ->row_array();

        if (!$project) {
            return null;
        }

        $this->append_relations($project);
        $this->append_counts($project);

        return $project;
    }

    public function save(array $project): int
    {
        $this->validate_project($project);

        $project_id = !empty($project['id'])
            ? (int) $project['id']
            : null;

        $now = date('Y-m-d H:i:s');

        $record = [
            'name' => trim((string) $project['name']),
            'description' => trim(
                (string) ($project['description'] ?? '')
            ),
            'location' => trim(
                (string) ($project['location'] ?? '')
            ),
            'start_date' => !empty($project['start_date'])
                ? $project['start_date']
                : null,
            'end_date' => !empty($project['end_date'])
                ? $project['end_date']
                : null,
            'status' => $project['status'] ?? 'planned',
            'id_users_project_lead' =>
                !empty($project['id_users_project_lead'])
                    ? (int) $project['id_users_project_lead']
                    : null,
            'color' => $project['color'] ?? '#7cbae8',
            'update_datetime' => $now,
        ];

        $this->db->trans_begin();

        try {
            if ($project_id) {
                if (!$this->find($project_id)) {
                    throw new RuntimeException(
                        'Projekt nicht gefunden.'
                    );
                }

                $this->db
                    ->where('id', $project_id)
                    ->update('planning_projects', $record);
            } else {
                $record['create_datetime'] = $now;

                $this->db->insert(
                    'planning_projects',
                    $record
                );

                $project_id = (int) $this->db->insert_id();
            }

            $this->save_relation(
                'planning_project_employees',
                'id_users',
                $project_id,
                $project['employees'] ?? [],
                $now
            );

            $this->save_relation(
                'planning_project_teams',
                'id_employee_teams',
                $project_id,
                $project['teams'] ?? [],
                $now
            );

            $this->save_relation(
                'planning_project_resources',
                'id_planning_resources',
                $project_id,
                $project['resources'] ?? [],
                $now
            );

            $this->save_relation(
                'planning_project_services',
                'id_services',
                $project_id,
                $project['services'] ?? [],
                $now
            );

            if ($this->db->trans_status() === false) {
                throw new RuntimeException(
                    'Das Projekt konnte nicht vollständig gespeichert werden.'
                );
            }

            $this->db->trans_commit();

            return $project_id;
        } catch (Throwable $e) {
            $this->db->trans_rollback();

            throw $e;
        }
    }

    public function delete(int $project_id): void
    {
        $project = $this->find($project_id);

        if (!$project) {
            throw new RuntimeException(
                'Projekt nicht gefunden.'
            );
        }

        $assignment_count = $this->db
            ->where('id_planning_projects', $project_id)
            ->count_all_results('planning_assignments');

        if ($assignment_count > 0) {
            throw new RuntimeException(
                'Das Projekt enthält bereits konkrete Einsätze und kann '
                . 'nicht gelöscht werden. Setzen Sie den Status stattdessen '
                . 'auf „Abgebrochen“.'
            );
        }

        $this->db->delete(
            'planning_projects',
            ['id' => $project_id]
        );
    }

    private function validate_project(array $project): void
    {
        $name = trim((string) ($project['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException(
                'Der Projektname ist erforderlich.'
            );
        }

        if (mb_strlen($name) > 256) {
            throw new InvalidArgumentException(
                'Der Projektname ist zu lang.'
            );
        }

        $status = $project['status'] ?? 'planned';

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException(
                'Der Projektstatus ist ungültig.'
            );
        }

        $start_date = $project['start_date'] ?? null;
        $end_date = $project['end_date'] ?? null;

        if (
            $start_date &&
            $end_date &&
            $end_date < $start_date
        ) {
            throw new InvalidArgumentException(
                'Das Projektende darf nicht vor dem Projektbeginn liegen.'
            );
        }

        $color = (string) ($project['color'] ?? '#7cbae8');

        if (!validate_color($color)) {
            throw new InvalidArgumentException(
                'Die Projektfarbe ist ungültig.'
            );
        }
    }

    private function save_relation(
        string $table,
        string $target_column,
        int $project_id,
        array $target_ids,
        string $now
    ): void {
        $this->db->delete(
            $table,
            ['id_planning_projects' => $project_id]
        );

        $seen = [];

        foreach ($target_ids as $target_id) {
            $target_id = (int) $target_id;

            if ($target_id < 1 || isset($seen[$target_id])) {
                continue;
            }

            $seen[$target_id] = true;

            $this->db->insert($table, [
                'id_planning_projects' => $project_id,
                $target_column => $target_id,
                'create_datetime' => $now,
            ]);
        }
    }

    private function append_relations(array &$project): void
    {
        $project_id = (int) $project['id'];

        $project['employees'] = $this->get_relation_ids(
            'planning_project_employees',
            'id_users',
            $project_id
        );

        $project['teams'] = $this->get_relation_ids(
            'planning_project_teams',
            'id_employee_teams',
            $project_id
        );

        $project['resources'] = $this->get_relation_ids(
            'planning_project_resources',
            'id_planning_resources',
            $project_id
        );

        $project['services'] = $this->get_relation_ids(
            'planning_project_services',
            'id_services',
            $project_id
        );
    }

    private function get_relation_ids(
        string $table,
        string $target_column,
        int $project_id
    ): array {
        $rows = $this->db
            ->select($target_column)
            ->where('id_planning_projects', $project_id)
            ->get($table)
            ->result_array();

        return array_map(
            static fn(array $row): int =>
                (int) $row[$target_column],
            $rows
        );
    }

    private function append_counts(array &$project): void
    {
        $project_id = (int) $project['id'];

        $project['phase_count'] = $this->db
            ->where('id_planning_projects', $project_id)
            ->count_all_results('planning_project_phases');

        $project['assignment_count'] = $this->db
            ->where('id_planning_projects', $project_id)
            ->count_all_results('planning_assignments');

        $project['employee_count'] = count(
            $project['employees'] ?? []
        );

        $project['team_count'] = count(
            $project['teams'] ?? []
        );

        $project['resource_count'] = count(
            $project['resources'] ?? []
        );
    }
}
