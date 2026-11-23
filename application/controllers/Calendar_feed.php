<?php defined('BASEPATH') or exit('No direct script access allowed');

class Calendar_feed extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model(
            'planning_calendar_subscriptions_model'
        );
    }

    public function ics(string $token = ''): void
    {
        $subscription =
            $this->planning_calendar_subscriptions_model
                ->find_active_by_token($token);

        if (!$subscription) {
            show_404();
            return;
        }

        $events =
            $this->planning_calendar_subscriptions_model
                ->get_assignment_events(
                    (int) $subscription[
                        'id_planning_calendars'
                    ]
                );

        $calendar_name =
            $subscription['calendar_name'] ??
            $subscription['name'];

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//MODUL//Einsatzkalender//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' .
                $this->escape($calendar_name),
            'X-WR-TIMEZONE:Europe/Berlin',
            'X-PUBLISHED-TTL:PT15M',
            'REFRESH-INTERVAL;VALUE=DURATION:PT15M',
        ];

        foreach ($events as $event) {
            $resources = $event['resources'] ?? [];

            $teams = array_column(
                $event['teams'] ?? [],
                'name'
            );

            $description = [];

            $description[] = 'EINSATZ';
            $description[] =
                $event['title'] ?? '';

            $description[] = '';
            $description[] = 'PROJEKT';
            $description[] =
                $event['project_name'] ?? '';

            if (!empty($event['phase_name'])) {
                $description[] = '';
                $description[] = 'PROJEKTBAUSTEIN';
                $description[] =
                    $event['phase_name'];
            }

            $customerName = trim(
                (string) (
                    ($event['customer_first_name'] ?? '') .
                    ' ' .
                    ($event['customer_last_name'] ?? '')
                )
            );

            $customerPhone = trim(
                (string) (
                    $event['customer_mobile_number'] ??
                    $event['customer_phone_number'] ??
                    ''
                )
            );

            $customerAddressParts = array_filter(
                [
                    trim(
                        (string) (
                            $event['customer_address'] ?? ''
                        )
                    ),
                    trim(
                        (string) (
                            ($event['customer_zip_code'] ?? '') .
                            ' ' .
                            ($event['customer_city'] ?? '')
                        )
                    ),
                ],
                static fn(string $value): bool =>
                    $value !== ''
            );

            $customerAddress = implode(
                ', ',
                $customerAddressParts
            );

            if (
                $customerName !== '' ||
                $customerPhone !== '' ||
                $customerAddress !== ''
            ) {
                $description[] = '';
                $description[] = 'KUNDE';

                if ($customerName !== '') {
                    $description[] = $customerName;
                }

                if ($customerAddress !== '') {
                    $description[] =
                        'Adresse: ' . $customerAddress;
                }

                if ($customerPhone !== '') {
                    $description[] =
                        'Telefon: ' . $customerPhone;
                }
            }

            $location = trim(
                (string) ($event['location'] ?? '')
            );

            if ($location === '') {
                $location = $customerAddress;
            }

            if ($location !== '') {
                $description[] = '';
                $description[] = 'EINSATZORT';
                $description[] = $location;
            }

            if ($teams) {
                $description[] = '';
                $description[] = 'TEAM';

                foreach ($teams as $team) {
                    $description[] = '• ' . $team;
                }
            }

            if ($resources) {
                $groupedResources = [];

                foreach ($resources as $resource) {
                    $resourceName = trim(
                        (string) (
                            $resource['name'] ?? ''
                        )
                    );

                    if ($resourceName === '') {
                        continue;
                    }

                    $category = trim(
                        (string) (
                            $resource['category_name'] ?? ''
                        )
                    );

                    if ($category === '') {
                        $category = 'Weitere Ressourcen';
                    }

                    $groupedResources[$category][] =
                        $resourceName;
                }

                if ($groupedResources) {
                    $description[] = '';
                    $description[] =
                        'ZUGETEILTE RESSOURCEN';

                    foreach (
                        $groupedResources as
                        $category => $items
                    ) {
                        $description[] = '';
                        $description[] =
                            $category . ':';

                        foreach (
                            array_unique($items) as $item
                        ) {
                            $description[] =
                                '• ' . $item;
                        }
                    }
                }
            }

            $notes = trim(
                (string) ($event['notes'] ?? '')
            );

            if ($notes !== '') {
                $description[] = '';
                $description[] = 'HINWEISE';
                $description[] = $notes;
            }

            $updated =
                $event['update_datetime'] ??
                $event['create_datetime'] ??
                date('Y-m-d H:i:s');

            $status =
                ($event['status'] ?? '') === 'cancelled'
                    ? 'CANCELLED'
                    : 'CONFIRMED';

            $lines = array_merge($lines, [
                'BEGIN:VEVENT',
                'UID:project-assignment-' .
                    (int) $event['id'] .
                    '@modul',
                'DTSTAMP:' . $this->utc($updated),
                'LAST-MODIFIED:' .
                    $this->utc($updated),
                'SEQUENCE:' .
                    max(
                        0,
                        (int) strtotime($updated)
                    ),
                'DTSTART;TZID=Europe/Berlin:' .
                    $this->local(
                        $event['start_datetime']
                    ),
                'DTEND;TZID=Europe/Berlin:' .
                    $this->local(
                        $event['end_datetime']
                    ),
                'SUMMARY:' .
                    $this->escape(
                        trim(
                            ($event['project_name'] ?? '') .
                            ' · ' .
                            ($event['title'] ?? '')
                        )
                    ),
                'LOCATION:' .
                    $this->escape($location),
                'DESCRIPTION:' .
                    $this->escape(
                        implode("\n", $description)
                    ),
                'STATUS:' . $status,
                'TRANSP:OPAQUE',
                'END:VEVENT',
            ]);
        }

        $lines[] = 'END:VCALENDAR';

        $content = $this->fold(
            implode("\r\n", $lines) . "\r\n"
        );

        $filename = preg_replace(
            '/[^a-z0-9\-]+/i',
            '-',
            strtolower($calendar_name)
        ) ?: 'einsatzkalender';

        $this->output
            ->set_content_type(
                'text/calendar',
                'utf-8'
            )
            ->set_header(
                'Content-Disposition: inline; filename="' .
                $filename .
                '.ics"'
            )
            ->set_header(
                'Cache-Control: no-cache, no-store, must-revalidate'
            )
            ->set_output($content);
    }

    private function local(string $datetime): string
    {
        return date(
            'Ymd\THis',
            strtotime($datetime)
        );
    }

    private function utc(string $datetime): string
    {
        return gmdate(
            'Ymd\THis\Z',
            strtotime($datetime)
        );
    }

    private function escape(string $value): string
    {
        return str_replace(
            ["\\", ";", ",", "\r\n", "\r", "\n"],
            ["\\\\", "\;", "\,", "\\n", "\\n", "\\n"],
            $value
        );
    }

    private function fold(string $content): string
    {
        $result = [];

        foreach (
            preg_split('/\r\n/', $content) as $line
        ) {
            while (strlen($line) > 73) {
                $result[] = substr($line, 0, 73);
                $line = ' ' . substr($line, 73);
            }

            $result[] = $line;
        }

        return implode("\r\n", $result);
    }
}
