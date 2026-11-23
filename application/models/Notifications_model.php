<?php defined('BASEPATH') or exit('No direct script access allowed');

class Notifications_model extends EA_Model
{
    public function sync_planning_impacts(): void
    {
        if (
            !$this->db->table_exists(
                'planning_assignment_impacts'
            )
        ) {
            return;
        }

        $impacts = $this->db
            ->select(
                'i.*,
                 a.title AS assignment_title,
                 a.id_planning_projects,
                 a.start_datetime,
                 a.end_datetime,
                 p.name AS project_name,
                 p.id_users_project_lead'
            )
            ->from('planning_assignment_impacts AS i')
            ->join(
                'planning_assignments AS a',
                'a.id = i.id_planning_assignments',
                'inner'
            )
            ->join(
                'planning_projects AS p',
                'p.id = a.id_planning_projects',
                'inner'
            )
            ->get()
            ->result_array();

        foreach ($impacts as $impact) {
            $key =
                'planning-impact:' .
                (int) $impact['id'];

            if (
                $impact['status'] === 'resolved'
            ) {
                $this->deactivate_by_key($key);
                continue;
            }

            $notification_id =
                $this->upsert([
                    'type' => 'planning_impact',
                    'severity' =>
                        $impact['severity'],
                    'title' =>
                        $this->impact_title($impact),
                    'message' =>
                        $impact['message'],
                    'entity_type' =>
                        'planning_assignment',
                    'entity_id' =>
                        (int) $impact[
                            'id_planning_assignments'
                        ],
                    'source_type' =>
                        'planning_assignment_impact',
                    'source_id' =>
                        (int) $impact['id'],
                    'action_url' =>
                        $this->impact_action_url(
                            $impact
                        ),
                    'deduplication_key' => $key,
                ]);

            $recipients =
                $this->impact_recipients($impact);

            foreach ($recipients as $user_id) {
                $this->ensure_recipient(
                    $notification_id,
                    $user_id,
                    $impact['severity'] ===
                        'critical'
                );
            }
        }
    }

    public function get_for_user(
        int $user_id,
        bool $include_deleted = false,
        int $limit = 100
    ): array {
        $now = date('Y-m-d H:i:s');

        $this->db
            ->select(
                'n.*,
                 nr.id AS recipient_id,
                 nr.state,
                 nr.is_read,
                 nr.read_datetime,
                 nr.snoozed_until,
                 nr.reminder_interval,
                 nr.next_reminder_datetime,
                 nr.is_pinned,
                 nr.deleted_datetime,
                 nr.last_displayed_datetime'
            )
            ->from('notification_recipients AS nr')
            ->join(
                'notifications AS n',
                'n.id = nr.id_notifications',
                'inner'
            )
            ->where('nr.id_users', $user_id)
            ->where('n.is_active', 1);

        if (!$include_deleted) {
            $this->db->where(
                'nr.deleted_datetime IS NULL',
                null,
                false
            );
        }

        $this->db
            ->group_start()
            ->where(
                'nr.snoozed_until IS NULL',
                null,
                false
            )
            ->or_where(
                'nr.snoozed_until <=',
                $now
            )
            ->group_end()
            ->order_by('nr.is_pinned', 'DESC')
            ->order_by(
                "FIELD(
                    n.severity,
                    'critical',
                    'warning',
                    'info',
                    'success'
                )",
                '',
                false
            )
            ->order_by(
                'n.update_datetime',
                'DESC'
            )
            ->limit($limit);

        $notifications = $this->db
            ->get()
            ->result_array();

        foreach ($notifications as &$notification) {
            $actionUrl = (string) (
                $notification['action_url'] ?? ''
            );

            $notification['action_label'] =
                str_contains(
                    $actionUrl,
                    'planning_resources'
                )
                    ? 'Ressource öffnen'
                    : 'Projekt öffnen';
        }

        unset($notification);

        return $notifications;
    }

    public function count_due(int $user_id): int
    {
        return count(
            array_filter(
                $this->get_for_user(
                    $user_id,
                    false,
                    250
                ),
                static fn(array $notification): bool =>
                    !(bool) $notification['is_read'] ||
                    !empty(
                        $notification[
                            'next_reminder_datetime'
                        ]
                    )
            )
        );
    }

    public function get_deleted_for_user(
        int $user_id,
        int $limit = 250
    ): array {
        return $this->db
            ->select(
                'n.*,
                 nr.id AS recipient_id,
                 nr.state,
                 nr.is_read,
                 nr.read_datetime,
                 nr.snoozed_until,
                 nr.reminder_interval,
                 nr.next_reminder_datetime,
                 nr.is_pinned,
                 nr.deleted_datetime'
            )
            ->from('notification_recipients AS nr')
            ->join(
                'notifications AS n',
                'n.id = nr.id_notifications',
                'inner'
            )
            ->where('nr.id_users', $user_id)
            ->where(
                'nr.deleted_datetime IS NOT NULL',
                null,
                false
            )
            ->order_by(
                'nr.deleted_datetime',
                'DESC'
            )
            ->limit($limit)
            ->get()
            ->result_array();
    }

    public function restore_for_user(
        int $recipient_id,
        int $user_id
    ): void {
        $recipient = $this->find_recipient(
            $recipient_id,
            $user_id
        );

        $this->update_recipient(
            $recipient_id,
            $user_id,
            [
                'state' => 'active',
                'deleted_datetime' => null,
                'snoozed_until' => null,
                'next_reminder_datetime' => null,
            ]
        );

        $this->add_history(
            $recipient,
            $user_id,
            'restored',
            'Meldung aus dem Papierkorb wiederhergestellt.'
        );
    }

    public function get_history(
        int $recipient_id,
        int $user_id
    ): array {
        return $this->db
            ->select(
                'notification_history.*,
                 users.first_name,
                 users.last_name'
            )
            ->from('notification_history')
            ->join(
                'users',
                'users.id =
                    notification_history.id_users',
                'left'
            )
            ->where(
                'notification_history.id_notification_recipients',
                $recipient_id
            )
            ->where(
                'notification_history.id_users',
                $user_id
            )
            ->order_by(
                'notification_history.create_datetime',
                'DESC'
            )
            ->get()
            ->result_array();
    }

    public function mark_read(
        int $recipient_id,
        int $user_id
    ): void {
        $recipient = $this->find_recipient(
            $recipient_id,
            $user_id
        );

        $this->update_recipient(
            $recipient_id,
            $user_id,
            [
                'is_read' => 1,
                'read_datetime' =>
                    date('Y-m-d H:i:s'),
            ]
        );

        $this->add_history(
            $recipient,
            $user_id,
            'read',
            'Meldung als gelesen markiert.'
        );
    }

    public function snooze(
        int $recipient_id,
        int $user_id,
        string $mode,
        ?string $until = null
    ): void {
        $recipient = $this->find_recipient(
            $recipient_id,
            $user_id
        );

        $now = new DateTimeImmutable();

        switch ($mode) {
            case '24h':
                $snoozed_until =
                    $now->modify('+24 hours');
                $interval = 'none';
                $next = null;
                break;

            case 'tomorrow':
                $snoozed_until =
                    $now
                        ->modify('tomorrow')
                        ->setTime(8, 0);
                $interval = 'none';
                $next = null;
                break;

            case 'week':
                $snoozed_until =
                    $now->modify('+7 days');
                $interval = 'none';
                $next = null;
                break;

            case 'daily':
                $snoozed_until =
                    $now->modify('+1 day');
                $interval = 'daily';
                $next = $snoozed_until;
                break;

            case 'weekly':
                $snoozed_until =
                    $now->modify('+7 days');
                $interval = 'weekly';
                $next = $snoozed_until;
                break;

            case 'custom':
                if (
                    !$until ||
                    strtotime($until) === false
                ) {
                    throw new InvalidArgumentException(
                        'Ungültige Wiedervorlage.'
                    );
                }

                $snoozed_until =
                    new DateTimeImmutable($until);
                $interval = 'custom';
                $next = $snoozed_until;
                break;

            default:
                throw new InvalidArgumentException(
                    'Ungültige Wiedervorlage.'
                );
        }

        $formatted =
            $snoozed_until->format('Y-m-d H:i:s');

        $this->update_recipient(
            $recipient_id,
            $user_id,
            [
                'state' => 'snoozed',
                'snoozed_until' => $formatted,
                'reminder_interval' => $interval,
                'next_reminder_datetime' =>
                    $next?->format(
                        'Y-m-d H:i:s'
                    ),
            ]
        );

        $this->add_history(
            $recipient,
            $user_id,
            'snoozed',
            'Wiedervorlage: ' .
                $mode .
                ' bis ' .
                $formatted
        );
    }

    public function pin(
        int $recipient_id,
        int $user_id,
        bool $pin
    ): void {
        $recipient = $this->find_recipient(
            $recipient_id,
            $user_id
        );

        $this->update_recipient(
            $recipient_id,
            $user_id,
            [
                'is_pinned' => $pin ? 1 : 0,
            ]
        );

        $this->add_history(
            $recipient,
            $user_id,
            $pin ? 'pinned' : 'unpinned',
            $pin
                ? 'Meldung permanent angepinnt.'
                : 'Anheftung der Meldung aufgehoben.'
        );
    }

    public function delete_for_user(
        int $recipient_id,
        int $user_id
    ): void {
        $recipient = $this->find_recipient(
            $recipient_id,
            $user_id
        );

        $this->update_recipient(
            $recipient_id,
            $user_id,
            [
                'state' => 'deleted',
                'deleted_datetime' =>
                    date('Y-m-d H:i:s'),
            ]
        );

        $this->add_history(
            $recipient,
            $user_id,
            'deleted',
            'Meldung in den Papierkorb verschoben.'
        );
    }

    private function upsert(array $data): int
    {
        $existing = $this->db
            ->get_where(
                'notifications',
                [
                    'deduplication_key' =>
                        $data['deduplication_key'],
                ]
            )
            ->row_array();

        $now = date('Y-m-d H:i:s');

        $record = $data + [
            'is_active' => 1,
        ];

        $record['update_datetime'] = $now;

        if ($existing) {
            $this->db
                ->where(
                    'id',
                    (int) $existing['id']
                )
                ->update(
                    'notifications',
                    $record
                );

            return (int) $existing['id'];
        }

        $record['create_datetime'] = $now;

        $this->db->insert(
            'notifications',
            $record
        );

        return (int) $this->db->insert_id();
    }

    private function ensure_recipient(
        int $notification_id,
        int $user_id,
        bool $pin = false
    ): void {
        if ($user_id <= 0) {
            return;
        }

        $existing = $this->db
            ->get_where(
                'notification_recipients',
                [
                    'id_notifications' =>
                        $notification_id,
                    'id_users' => $user_id,
                ]
            )
            ->row_array();

        if ($existing) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->insert(
            'notification_recipients',
            [
                'id_notifications' =>
                    $notification_id,
                'id_users' => $user_id,
                'state' => 'active',
                'is_read' => 0,
                'read_datetime' => null,
                'snoozed_until' => null,
                'reminder_interval' => 'none',
                'next_reminder_datetime' =>
                    null,
                'is_pinned' => $pin ? 1 : 0,
                'deleted_datetime' => null,
                'last_displayed_datetime' =>
                    null,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]
        );
    }

    private function impact_recipients(
        array $impact
    ): array {
        $recipients = [];

        if (
            !empty(
                $impact[
                    'id_users_project_lead'
                ]
            )
        ) {
            $recipients[] =
                (int) $impact[
                    'id_users_project_lead'
                ];
        }

        $admins = $this->db
            ->select('users.id')
            ->from('users')
            ->join(
                'roles',
                'roles.id = users.id_roles',
                'inner'
            )
            ->where('roles.slug', DB_SLUG_ADMIN)
            ->get()
            ->result_array();

        foreach ($admins as $admin) {
            $recipients[] =
                (int) $admin['id'];
        }

        return array_values(
            array_unique($recipients)
        );
    }

    private function impact_action_url(
        array $impact
    ): string {
        if (
            ($impact['source_type'] ?? '') ===
            'resource_unavailability'
        ) {
            $unavailability = $this->db
                ->select('id_planning_resources')
                ->get_where(
                    'planning_resource_unavailabilities',
                    [
                        'id' =>
                            (int) ($impact['source_id'] ?? 0),
                    ]
                )
                ->row_array();

            if (
                !empty(
                    $unavailability[
                        'id_planning_resources'
                    ]
                )
            ) {
                return site_url(
                    'planning_resources' .
                    '?resource_id=' .
                    (int) $unavailability[
                        'id_planning_resources'
                    ]
                );
            }
        }

        return site_url(
            'planning_projects' .
            '?project_id=' .
            (int) (
                $impact['id_planning_projects'] ?? 0
            )
        );
    }

    private function impact_title(
        array $impact
    ): string {
        $prefix =
            $impact['severity'] === 'critical'
                ? 'Kritische Beeinträchtigung'
                : 'Einsatz beeinträchtigt';

        return
            $prefix .
            ': ' .
            ($impact['assignment_title'] ??
                'Einsatz');
    }

    private function deactivate_by_key(
        string $key
    ): void {
        $this->db
            ->where(
                'deduplication_key',
                $key
            )
            ->update(
                'notifications',
                [
                    'is_active' => 0,
                    'update_datetime' =>
                        date('Y-m-d H:i:s'),
                ]
            );
    }

    private function find_recipient(
        int $recipient_id,
        int $user_id
    ): array {
        $recipient = $this->db
            ->get_where(
                'notification_recipients',
                [
                    'id' => $recipient_id,
                    'id_users' => $user_id,
                ]
            )
            ->row_array();

        if (!$recipient) {
            throw new RuntimeException(
                'Meldung wurde nicht gefunden.'
            );
        }

        return $recipient;
    }

    private function add_history(
        array $recipient,
        int $user_id,
        string $action,
        string $details = ''
    ): void {
        if (
            !$this->db->table_exists(
                'notification_history'
            )
        ) {
            return;
        }

        $this->db->insert(
            'notification_history',
            [
                'id_notification_recipients' =>
                    (int) $recipient['id'],
                'id_notifications' =>
                    (int) $recipient[
                        'id_notifications'
                    ],
                'id_users' => $user_id,
                'action' => $action,
                'details' => $details,
                'create_datetime' =>
                    date('Y-m-d H:i:s'),
            ]
        );
    }

    private function update_recipient(
        int $recipient_id,
        int $user_id,
        array $data
    ): void {
        $data['update_datetime'] =
            date('Y-m-d H:i:s');

        $this->db
            ->where('id', $recipient_id)
            ->where('id_users', $user_id)
            ->update(
                'notification_recipients',
                $data
            );
    }
}
