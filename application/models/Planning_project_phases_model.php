<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_project_phases_model extends EA_Model
{
    private const ALLOWED_STATUSES = [
        'planned',
        'active',
        'paused',
        'completed',
        'cancelled',
    ];

    public function get_by_project(int $project_id): array
    {
        return $this->db
            ->select(
                'planning_project_phases.*,
                 parent.name AS parent_name'
            )
            ->from('planning_project_phases')
            ->join(
                'planning_project_phases AS parent',
                'parent.id = planning_project_phases.id_parent',
                'left'
            )
            ->where(
                'planning_project_phases.id_planning_projects',
                $project_id
            )
            ->order_by(
                'planning_project_phases.sort_order',
                'ASC'
            )
            ->order_by(
                'planning_project_phases.start_date',
                'ASC'
            )
            ->order_by(
                'planning_project_phases.id',
                'ASC'
            )
            ->get()
            ->result_array();
    }

    public function find(int $phase_id): ?array
    {
        $phase = $this->db
            ->get_where(
                'planning_project_phases',
                ['id' => $phase_id]
            )
            ->row_array();

        return $phase ?: null;
    }

    public function save(array $phase): int
    {
        $this->validate_phase($phase);

        $phase_id = !empty($phase['id'])
            ? (int) $phase['id']
            : null;

        $project_id =
            (int) $phase['id_planning_projects'];

        $parent_id = !empty($phase['id_parent'])
            ? (int) $phase['id_parent']
            : null;

        if (
            $phase_id &&
            $parent_id &&
            $phase_id === $parent_id
        ) {
            throw new InvalidArgumentException(
                'Ein Baustein kann nicht sein eigener übergeordneter Baustein sein.'
            );
        }

        if ($parent_id) {
            $parent = $this->find($parent_id);

            if (
                !$parent ||
                (int) $parent['id_planning_projects'] !==
                    $project_id
            ) {
                throw new InvalidArgumentException(
                    'Der übergeordnete Baustein gehört nicht zu diesem Projekt.'
                );
            }

            if (
                $phase_id &&
                $this->is_descendant(
                    $parent_id,
                    $phase_id
                )
            ) {
                throw new InvalidArgumentException(
                    'Die Bausteinhierarchie würde eine Endlosschleife erzeugen.'
                );
            }
        }

        $project = $this->db
            ->get_where(
                'planning_projects',
                ['id' => $project_id]
            )
            ->row_array();

        if (!$project) {
            throw new RuntimeException(
                'Das zugehörige Projekt wurde nicht gefunden.'
            );
        }

        if (
            !empty($project['start_date']) &&
            !empty($phase['start_date']) &&
            $phase['start_date'] < $project['start_date']
        ) {
            throw new InvalidArgumentException(
                'Der Baustein darf nicht vor dem Projektbeginn starten.'
            );
        }

        if (
            !empty($project['end_date']) &&
            !empty($phase['end_date']) &&
            $phase['end_date'] > $project['end_date']
        ) {
            throw new InvalidArgumentException(
                'Der Baustein darf nicht nach dem Projektende enden.'
            );
        }

        $now = date('Y-m-d H:i:s');

        $record = [
            'id_planning_projects' => $project_id,
            'id_parent' => $parent_id,
            'name' => trim((string) $phase['name']),
            'description' => trim(
                (string) ($phase['description'] ?? '')
            ),
            'start_date' => $phase['start_date'],
            'end_date' => $phase['end_date'],
            'progress_percent' => min(
                100,
                max(
                    0,
                    (int) (
                        $phase['progress_percent'] ?? 0
                    )
                )
            ),
            'status' => $phase['status'] ?? 'planned',
            'color' => $phase['color'] ?? '#7cbae8',
            'sort_order' => $phase_id
                ? (int) (
                    $this->find($phase_id)['sort_order'] ?? 0
                )
                : $this->next_sort_order($project_id),
            'update_datetime' => $now,
        ];

        if ($phase_id) {
            $existing = $this->find($phase_id);

            if (
                !$existing ||
                (int) $existing['id_planning_projects'] !==
                    $project_id
            ) {
                throw new RuntimeException(
                    'Projektbaustein nicht gefunden.'
                );
            }

            $this->db
                ->where('id', $phase_id)
                ->update(
                    'planning_project_phases',
                    $record
                );

            return $phase_id;
        }

        $record['create_datetime'] = $now;

        $this->db->insert(
            'planning_project_phases',
            $record
        );

        return (int) $this->db->insert_id();
    }

    public function delete(int $phase_id): void
    {
        $phase = $this->find($phase_id);

        if (!$phase) {
            throw new RuntimeException(
                'Projektbaustein nicht gefunden.'
            );
        }

        $assignment_count = $this->db
            ->where(
                'id_planning_project_phases',
                $phase_id
            )
            ->count_all_results('planning_assignments');

        if ($assignment_count > 0) {
            throw new RuntimeException(
                'Der Baustein enthält bereits konkrete Einsätze und kann nicht gelöscht werden.'
            );
        }

        $child_count = $this->db
            ->where('id_parent', $phase_id)
            ->count_all_results(
                'planning_project_phases'
            );

        if ($child_count > 0) {
            throw new RuntimeException(
                'Der Baustein enthält noch untergeordnete Bausteine.'
            );
        }

        $this->db->delete(
            'planning_project_phases',
            ['id' => $phase_id]
        );
    }

    private function validate_phase(array $phase): void
    {
        if (
            empty($phase['id_planning_projects']) ||
            trim((string) ($phase['name'] ?? '')) === '' ||
            empty($phase['start_date']) ||
            empty($phase['end_date'])
        ) {
            throw new InvalidArgumentException(
                'Projekt, Bezeichnung, Beginn und Ende sind erforderlich.'
            );
        }

        if ($phase['end_date'] < $phase['start_date']) {
            throw new InvalidArgumentException(
                'Das Ende darf nicht vor dem Beginn liegen.'
            );
        }

        $status = $phase['status'] ?? 'planned';

        if (!in_array(
            $status,
            self::ALLOWED_STATUSES,
            true
        )) {
            throw new InvalidArgumentException(
                'Der Bausteinstatus ist ungültig.'
            );
        }

        $color = (string) (
            $phase['color'] ?? '#7cbae8'
        );

        if (!validate_color($color)) {
            throw new InvalidArgumentException(
                'Die Bausteinfarbe ist ungültig.'
            );
        }
    }

    private function next_sort_order(
        int $project_id
    ): int {
        $row = $this->db
            ->select_max(
                'sort_order',
                'max_sort_order'
            )
            ->where(
                'id_planning_projects',
                $project_id
            )
            ->get('planning_project_phases')
            ->row_array();

        return ((int) (
            $row['max_sort_order'] ?? 0
        )) + 10;
    }

    private function is_descendant(
        int $possible_parent_id,
        int $phase_id
    ): bool {
        $visited = [];

        while (
            $possible_parent_id > 0 &&
            !isset($visited[$possible_parent_id])
        ) {
            if ($possible_parent_id === $phase_id) {
                return true;
            }

            $visited[$possible_parent_id] = true;

            $phase = $this->db
                ->select('id_parent')
                ->get_where(
                    'planning_project_phases',
                    ['id' => $possible_parent_id]
                )
                ->row_array();

            $possible_parent_id =
                !empty($phase['id_parent'])
                    ? (int) $phase['id_parent']
                    : 0;
        }

        return false;
    }
}
