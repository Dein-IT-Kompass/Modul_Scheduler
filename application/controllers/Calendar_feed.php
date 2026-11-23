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
            $resources = array_column(
                $event['resources'] ?? [],
                'name'
            );

            $teams = array_column(
                $event['teams'] ?? [],
                'name'
            );

            $description = [
                'Projekt: ' .
                    ($event['project_name'] ?? ''),
                'Einsatz: ' .
                    ($event['title'] ?? ''),
            ];

            if (!empty($event['phase_name'])) {
                $description[] =
                    'Baustein: ' . $event['phase_name'];
            }

            if ($resources) {
                $description[] =
                    'Ressourcen: ' .
                    implode(', ', $resources);
            }

            if ($teams) {
                $description[] =
                    'Teams: ' . implode(', ', $teams);
            }

            if (!empty($event['notes'])) {
                $description[] =
                    'Hinweis: ' . $event['notes'];
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
                    $this->escape(
                        $event['location'] ?? ''
                    ),
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
