<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_resource_unavailabilities_model extends EA_Model
{
    private const ALLOWED_TYPES = [
        'defect',
        'maintenance',
        'blocked',
        'other',
    ];

    public function get_by_resource(
        int $resource_id
    ): array {
        return $this->db
            ->select(
                'planning_resource_unavailabilities.*,
                 users.first_name AS creator_first_name,
                 users.last_name AS creator_last_name'
            )
            ->from('planning_resource_unavailabilities')
            ->join(
                'users',
                'users.id =
                    planning_resource_unavailabilities.id_users_created_by',
                'left'
            )
            ->where(
                'planning_resource_unavailabilities.id_planning_resources',
                $resource_id
            )
            ->order_by(
                'planning_resource_unavailabilities.status',
                'ASC'
            )
            ->order_by(
                'planning_resource_unavailabilities.start_datetime',
                'DESC'
            )
            ->get()
            ->result_array();
    }

    public function save(
        array $unavailability,
        int $user_id
    ): int {
        $this->validate($unavailability);

        $id = !empty($unavailability['id'])
            ? (int) $unavailability['id']
            : null;

        $resource_id = (int) (
            $unavailability['id_planning_resources'] ?? 0
        );

        $resource_exists = $this->db
            ->where('id', $resource_id)
            ->count_all_results(
                'planning_resources'
            ) > 0;

        if (!$resource_exists) {
            throw new RuntimeException(
                'Die Ressource wurde nicht gefunden.'
            );
        }

        $now = date('Y-m-d H:i:s');

        $record = [
            'id_planning_resources' => $resource_id,
            'type' =>
                (string) $unavailability['type'],
            'start_datetime' =>
                (string) $unavailability[
                    'start_datetime'
                ],
            'end_datetime' =>
                !empty($unavailability['end_datetime'])
                    ? (string) $unavailability[
                        'end_datetime'
                    ]
                    : null,
            'status' => 'active',
            'title' => trim(
                (string) (
                    $unavailability['title'] ?? ''
                )
            ),
            'notes' => trim(
                (string) (
                    $unavailability['notes'] ?? ''
                )
            ),
            'update_datetime' => $now,
        ];

        if ($id) {
            $existing = $this->db
                ->get_where(
                    'planning_resource_unavailabilities',
                    ['id' => $id]
                )
                ->row_array();

            if (
                !$existing ||
                (int) $existing[
                    'id_planning_resources'
                ] !== $resource_id
            ) {
                throw new RuntimeException(
                    'Die Nichtverfügbarkeit wurde nicht gefunden.'
                );
            }

            $this->db
                ->where('id', $id)
                ->update(
                    'planning_resource_unavailabilities',
                    $record
                );

            return $id;
        }

        $record['id_users_created_by'] =
            $user_id ?: null;
        $record['create_datetime'] = $now;

        if (
            !$this->db->insert(
                'planning_resource_unavailabilities',
                $record
            )
        ) {
            throw new RuntimeException(
                'Die Nichtverfügbarkeit konnte nicht gespeichert werden.'
            );
        }

        return (int) $this->db->insert_id();
    }

    public function resolve(
        int $id,
        int $resource_id
    ): void {
        $existing = $this->db
            ->get_where(
                'planning_resource_unavailabilities',
                [
                    'id' => $id,
                    'id_planning_resources' =>
                        $resource_id,
                ]
            )
            ->row_array();

        if (!$existing) {
            throw new RuntimeException(
                'Die Nichtverfügbarkeit wurde nicht gefunden.'
            );
        }

        if ($existing['status'] === 'resolved') {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $end_datetime =
            !empty($existing['end_datetime']) &&
            strtotime($existing['end_datetime']) < time()
                ? $existing['end_datetime']
                : $now;

        $this->db
            ->where('id', $id)
            ->update(
                'planning_resource_unavailabilities',
                [
                    'status' => 'resolved',
                    'end_datetime' => $end_datetime,
                    'update_datetime' => $now,
                ]
            );
    }

    private function validate(
        array $unavailability
    ): void {
        $resource_id = (int) (
            $unavailability['id_planning_resources']
            ?? 0
        );

        if ($resource_id < 1) {
            throw new InvalidArgumentException(
                'Bitte zuerst eine Ressource auswählen.'
            );
        }

        $type = (string) (
            $unavailability['type'] ?? ''
        );

        if (
            !in_array(
                $type,
                self::ALLOWED_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Der Typ der Nichtverfügbarkeit ist ungültig.'
            );
        }

        $start = (string) (
            $unavailability['start_datetime'] ?? ''
        );

        $end = !empty(
            $unavailability['end_datetime']
        )
            ? (string) $unavailability[
                'end_datetime'
            ]
            : null;

        if (
            $start === '' ||
            strtotime($start) === false
        ) {
            throw new InvalidArgumentException(
                'Ein gültiger Beginn ist erforderlich.'
            );
        }

        if (
            $end !== null &&
            (
                strtotime($end) === false ||
                strtotime($end) <= strtotime($start)
            )
        ) {
            throw new InvalidArgumentException(
                'Das Ende muss nach dem Beginn liegen.'
            );
        }

        if (
            $type === 'maintenance' &&
            $end === null
        ) {
            throw new InvalidArgumentException(
                'Für ein Wartungsfenster ist ein Endzeitpunkt erforderlich.'
            );
        }
    }
}
