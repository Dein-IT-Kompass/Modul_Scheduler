<?php defined('BASEPATH') or exit('No direct script access allowed');

class Planning_resource_categories_model extends EA_Model
{
    public function get_all(): array
    {
        $categories = $this->db
            ->select(
                'categories.*,
                 parent.name AS parent_name'
            )
            ->from('planning_resource_categories AS categories')
            ->join(
                'planning_resource_categories AS parent',
                'parent.id = categories.id_parent',
                'left'
            )
            ->order_by('categories.sort_order', 'ASC')
            ->order_by('categories.name', 'ASC')
            ->get()
            ->result_array();

        foreach ($categories as &$category) {
            $category_id = (int) $category['id'];

            $descendant_ids = $this->get_descendant_ids(
                $category_id
            );

            $category_ids = array_merge(
                [$category_id],
                $descendant_ids
            );

            $category['direct_resource_count'] = $this->db
                ->where(
                    'id_planning_resource_categories',
                    $category_id
                )
                ->count_all_results('planning_resources');

            $category['resource_count'] = $this->db
                ->where_in(
                    'id_planning_resource_categories',
                    $category_ids
                )
                ->count_all_results('planning_resources');

            $category['child_count'] = count(
                $descendant_ids
            );
        }

        unset($category);

        return $categories;
    }

    public function find(int $category_id): ?array
    {
        $category = $this->db
            ->get_where(
                'planning_resource_categories',
                ['id' => $category_id]
            )
            ->row_array();

        return $category ?: null;
    }

    public function save(array $category): int
    {
        $this->validate_category($category);

        $category_id = !empty($category['id'])
            ? (int) $category['id']
            : null;

        $parent_id = !empty($category['id_parent'])
            ? (int) $category['id_parent']
            : null;

        if (
            $category_id &&
            $parent_id &&
            $category_id === $parent_id
        ) {
            throw new InvalidArgumentException(
                'Eine Kategorie kann nicht ihre eigene Oberkategorie sein.'
            );
        }

        if (
            $category_id &&
            $parent_id &&
            $this->is_descendant($parent_id, $category_id)
        ) {
            throw new InvalidArgumentException(
                'Die gewählte Oberkategorie würde eine Endlosschleife erzeugen.'
            );
        }

        $now = date('Y-m-d H:i:s');
        $slug = $this->create_unique_slug(
            trim((string) $category['name']),
            $category_id
        );

        $record = [
            'name' => trim((string) $category['name']),
            'slug' => $slug,
            'id_parent' => $parent_id,
            'description' => trim(
                (string) ($category['description'] ?? '')
            ),
            'color' => $category['color'] ?? '#7cbae8',
            'icon' => trim(
                (string) ($category['icon'] ?? 'fa-box')
            ) ?: 'fa-box',
            'sort_order' => $category_id
                ? (int) (
                    $this->find($category_id)['sort_order'] ?? 0
                )
                : $this->next_sort_order(),
            'is_active' => !empty($category['is_active']) ? 1 : 0,
            'update_datetime' => $now,
        ];

        if ($category_id) {
            if (!$this->find($category_id)) {
                throw new RuntimeException(
                    'Ressourcenkategorie nicht gefunden.'
                );
            }

            $this->db
                ->where('id', $category_id)
                ->update(
                    'planning_resource_categories',
                    $record
                );

            return $category_id;
        }

        $record['create_datetime'] = $now;

        $this->db->insert(
            'planning_resource_categories',
            $record
        );

        return (int) $this->db->insert_id();
    }

    public function delete(int $category_id): void
    {
        $category = $this->find($category_id);

        if (!$category) {
            throw new RuntimeException(
                'Ressourcenkategorie nicht gefunden.'
            );
        }

        $resource_count = $this->db
            ->where(
                'id_planning_resource_categories',
                $category_id
            )
            ->count_all_results('planning_resources');

        if ($resource_count > 0) {
            throw new RuntimeException(
                'Die Kategorie kann nicht gelöscht werden, weil ihr noch '
                . $resource_count
                . ' Ressource(n) zugeordnet sind. Ordnen Sie die Ressourcen '
                . 'zuerst einer anderen Kategorie zu oder deaktivieren Sie '
                . 'die Kategorie.'
            );
        }

        $child_count = $this->db
            ->where('id_parent', $category_id)
            ->count_all_results(
                'planning_resource_categories'
            );

        if ($child_count > 0) {
            throw new RuntimeException(
                'Die Kategorie kann nicht gelöscht werden, weil sie noch '
                . $child_count
                . ' Unterkategorie(n) enthält.'
            );
        }

        $this->db->delete(
            'planning_resource_categories',
            ['id' => $category_id]
        );
    }

    private function next_sort_order(): int
    {
        $row = $this->db
            ->select_max('sort_order', 'max_sort_order')
            ->get('planning_resource_categories')
            ->row_array();

        return ((int) ($row['max_sort_order'] ?? 0)) + 10;
    }

    private function get_descendant_ids(
        int $category_id
    ): array {
        $result = [];
        $pending = [$category_id];
        $visited = [];

        while ($pending) {
            $parent_id = array_shift($pending);

            if (isset($visited[$parent_id])) {
                continue;
            }

            $visited[$parent_id] = true;

            $children = $this->db
                ->select('id')
                ->where('id_parent', $parent_id)
                ->get('planning_resource_categories')
                ->result_array();

            foreach ($children as $child) {
                $child_id = (int) $child['id'];

                if (isset($visited[$child_id])) {
                    continue;
                }

                $result[] = $child_id;
                $pending[] = $child_id;
            }
        }

        return array_values(
            array_unique($result)
        );
    }

    private function validate_category(array $category): void
    {
        $name = trim((string) ($category['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException(
                'Der Kategoriename ist erforderlich.'
            );
        }

        if (mb_strlen($name) > 256) {
            throw new InvalidArgumentException(
                'Der Kategoriename ist zu lang.'
            );
        }

        $color = (string) (
            $category['color'] ?? '#7cbae8'
        );

        if (!validate_color($color)) {
            throw new InvalidArgumentException(
                'Die Kategorienfarbe ist ungültig.'
            );
        }
    }

    private function create_unique_slug(
        string $name,
        ?int $ignore_id = null
    ): string {
        $slug = strtolower($name);
        $slug = preg_replace(
            '/[^a-z0-9äöüß]+/u',
            '-',
            $slug
        );
        $slug = strtr(
            $slug,
            [
                'ä' => 'ae',
                'ö' => 'oe',
                'ü' => 'ue',
                'ß' => 'ss',
            ]
        );
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'category';
        }

        $candidate = $slug;
        $suffix = 2;

        while (true) {
            $this->db
                ->from('planning_resource_categories')
                ->where('slug', $candidate);

            if ($ignore_id !== null) {
                $this->db->where('id !=', $ignore_id);
            }

            if ($this->db->count_all_results() === 0) {
                return $candidate;
            }

            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }
    }

    private function is_descendant(
        int $possible_parent_id,
        int $category_id
    ): bool {
        $visited = [];

        while (
            $possible_parent_id > 0 &&
            !isset($visited[$possible_parent_id])
        ) {
            $visited[$possible_parent_id] = true;

            if ($possible_parent_id === $category_id) {
                return true;
            }

            $parent = $this->db
                ->select('id_parent')
                ->get_where(
                    'planning_resource_categories',
                    ['id' => $possible_parent_id]
                )
                ->row_array();

            $possible_parent_id = !empty($parent['id_parent'])
                ? (int) $parent['id_parent']
                : 0;
        }

        return false;
    }
}
