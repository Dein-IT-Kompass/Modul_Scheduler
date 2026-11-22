<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_resources_model extends EA_Model
{
    private const ALLOWED_TYPES = [
        'vehicle',
        'machine',
        'equipment',
        'room',
        'workplace',
        'other',
    ];

    private const ALLOWED_LOCATION_BEHAVIORS = [
        'stationary',
        'mobile',
        'carrier_bound',
        'project_bound',
    ];

    public function get_all(): array
    {
        return $this->db
            ->select(
                'planning_resources.*,
                 planning_resource_categories.name AS category_name,
                 carrier.name AS carrier_name'
            )
            ->from('planning_resources')
            ->join(
                'planning_resource_categories',
                'planning_resource_categories.id =
                    planning_resources.id_planning_resource_categories',
                'left'
            )
            ->join(
                'planning_resources AS carrier',
                'carrier.id = planning_resources.id_carrier_resource',
                'left'
            )
            ->order_by('planning_resources.is_active', 'DESC')
            ->order_by('planning_resources.resource_type', 'ASC')
            ->order_by('planning_resources.name', 'ASC')
            ->get()
            ->result_array();
    }

    public function find(int $resource_id): ?array
    {
        $resource = $this->db
            ->get_where('planning_resources', ['id' => $resource_id])
            ->row_array();

        return $resource ?: null;
    }

    public function save(array $resource): int
    {
        $resource['resource_type'] =
            $this->resolve_resource_type(
                !empty($resource['id_planning_resource_categories'])
                    ? (int) $resource[
                        'id_planning_resource_categories'
                    ]
                    : null
            );

        $this->validate_resource($resource);

        $now = date('Y-m-d H:i:s');

        $record = [
            'name' => trim((string) $resource['name']),
            'resource_type' => $resource['resource_type'],
            'description' => trim((string) ($resource['description'] ?? '')),
            'location' => trim((string) ($resource['location'] ?? '')),
            'location_behavior' =>
                $resource['location_behavior'] ?? 'mobile',
            'id_carrier_resource' =>
                ($resource['location_behavior'] ?? 'mobile') ===
                    'carrier_bound'
                    && !empty($resource['id_carrier_resource'])
                        ? (int) $resource['id_carrier_resource']
                        : null,
            'id_planning_resource_categories' =>
                !empty($resource['id_planning_resource_categories'])
                    ? (int) $resource[
                        'id_planning_resource_categories'
                    ]
                    : null,
            'inventory_number' => trim(
                (string) ($resource['inventory_number'] ?? '')
            ),
            'color' => $resource['color'] ?? '#7cbae8',
            'is_active' => !empty($resource['is_active']) ? 1 : 0,
            'update_datetime' => $now,
        ];

        if (!empty($resource['id'])) {
            $resource_id = (int) $resource['id'];

            if (!$this->find($resource_id)) {
                throw new RuntimeException('Ressource nicht gefunden.');
            }

            $this->assert_inventory_number_available(
                $record['inventory_number'],
                $resource_id
            );

            if (
                !$this->db
                    ->where('id', $resource_id)
                    ->update('planning_resources', $record)
            ) {
                throw new RuntimeException(
                    'Ressource konnte nicht aktualisiert werden.'
                );
            }

            return $resource_id;
        }

        $this->assert_inventory_number_available(
            $record['inventory_number']
        );

        $record['create_datetime'] = $now;

        if (!$this->db->insert('planning_resources', $record)) {
            throw new RuntimeException(
                'Ressource konnte nicht gespeichert werden.'
            );
        }

        return (int) $this->db->insert_id();
    }

    public function delete(int $resource_id): void
    {
        if (!$this->find($resource_id)) {
            throw new RuntimeException('Ressource nicht gefunden.');
        }

        $assignments = $this->db
            ->where('id_planning_resources', $resource_id)
            ->count_all_results('planning_project_resources');

        if ($assignments > 0) {
            throw new RuntimeException(
                'Die Ressource ist bereits einem Projekt zugeordnet und kann nicht gelöscht werden. '
                . 'Deaktivieren Sie die Ressource stattdessen.'
            );
        }

        if (
            !$this->db->delete(
                'planning_resources',
                ['id' => $resource_id]
            )
        ) {
            throw new RuntimeException(
                'Ressource konnte nicht gelöscht werden.'
            );
        }
    }

    private function resolve_resource_type(
        ?int $category_id
    ): string {
        if (!$category_id) {
            return 'other';
        }

        $mapping = [
            'vehicles' => 'vehicle',
            'machines' => 'machine',
            'equipment' => 'equipment',
            'rooms' => 'room',
            'workplaces' => 'workplace',
            'other' => 'other',
        ];

        $visited = [];

        while ($category_id && !isset($visited[$category_id])) {
            $visited[$category_id] = true;

            $category = $this->db
                ->select('id, slug, id_parent')
                ->get_where(
                    'planning_resource_categories',
                    ['id' => $category_id]
                )
                ->row_array();

            if (!$category) {
                return 'other';
            }

            if (isset($mapping[$category['slug']])) {
                return $mapping[$category['slug']];
            }

            $category_id = !empty($category['id_parent'])
                ? (int) $category['id_parent']
                : null;
        }

        return 'other';
    }

    private function validate_resource(array $resource): void
    {
        $name = trim((string) ($resource['name'] ?? ''));
        $type = $resource['resource_type'] ?? '';

        if ($name === '') {
            throw new InvalidArgumentException(
                'Der Ressourcenname ist erforderlich.'
            );
        }

        if (mb_strlen($name) > 256) {
            throw new InvalidArgumentException(
                'Der Ressourcenname ist zu lang.'
            );
        }

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(
                'Der Ressourcentyp ist ungültig.'
            );
        }

        $location_behavior =
            $resource['location_behavior'] ?? 'mobile';

        if (
            !in_array(
                $location_behavior,
                self::ALLOWED_LOCATION_BEHAVIORS,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Das Standortverhalten ist ungültig.'
            );
        }

        if (
            $location_behavior === 'carrier_bound' &&
            empty($resource['id_carrier_resource'])
        ) {
            throw new InvalidArgumentException(
                'Für eine trägergebundene Ressource muss eine '
                . 'Trägerressource ausgewählt werden.'
            );
        }

        if (
            $location_behavior === 'carrier_bound' &&
            !empty($resource['id']) &&
            (int) $resource['id'] ===
                (int) $resource['id_carrier_resource']
        ) {
            throw new InvalidArgumentException(
                'Eine Ressource kann nicht ihr eigener Träger sein.'
            );
        }

        $color = (string) ($resource['color'] ?? '#7cbae8');

        if (!validate_color($color)) {
            throw new InvalidArgumentException(
                'Die Ressourcenfarbe ist ungültig.'
            );
        }
    }

    private function assert_inventory_number_available(
        string $inventory_number,
        ?int $ignore_resource_id = null
    ): void {
        if ($inventory_number === '') {
            return;
        }

        $this->db
            ->from('planning_resources')
            ->where('inventory_number', $inventory_number);

        if ($ignore_resource_id !== null) {
            $this->db->where('id !=', $ignore_resource_id);
        }

        if ($this->db->count_all_results() > 0) {
            throw new RuntimeException(
                'Die Inventarnummer ist bereits vergeben.'
            );
        }
    }
}
