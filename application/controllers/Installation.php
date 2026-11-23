<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.1.0
 * ---------------------------------------------------------------------------- */

/**
 * Installation controller.
 *
 * Handles the installation related operations.
 *
 * @package Controllers
 */
class Installation extends EA_Controller
{
    /**
     * Installation constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('admins_model');
        $this->load->model('settings_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('customers_model');

        $this->load->library('instance');
    }

    /**
     * Display the installation page.
     */
    public function index(): void
{
    method('get');

    if (is_app_partially_installed()) {
        show_error(
            'Für den aktuell konfigurierten Tabellenpräfix wurde eine unvollständige MODUL-Scheduler-Installation erkannt. '
            . 'Bitte verwenden Sie einen freien Tabellenpräfix oder entfernen Sie ausschließlich die Tabellen dieses Präfixes.',
            409,
            'Unvollständige MODUL-Scheduler-Installation',
        );
    }

    if (is_app_installed()) {
        redirect();
        return;
    }

    $this->load->view('pages/installation', [
        'base_url' => config('base_url'),
    ]);
}

    /**
     * Installs Module Scheduler on the server.
     */
    public function perform(): void
    {
        try {
            method('post');
if (is_app_partially_installed()) {
    throw new RuntimeException(
        'Für den aktuell konfigurierten Tabellenpräfix existiert bereits eine unvollständige Installation.',
    );
}


            if (is_app_installed()) {
                return;
            }

            check('admin', 'array');
            check('company', 'array');
            check('database', 'array');
            check('enabled_modules', 'array|null');
            check('resource_icon_packs', 'array|null');

            $admin = request('admin');
            $company = request('company');
            $database = request('database');

            $this->configure_database_namespace(
                $database
            );


            $allowed_modules = ['scheduler', 'erp'];
            $enabled_modules = array_values(array_unique(array_intersect(
                array_map('strval', request('enabled_modules', ['scheduler'])),
                $allowed_modules
            )));

            if (!in_array('scheduler', $enabled_modules, true)) {
                array_unshift($enabled_modules, 'scheduler');
            }

            $allowed_icon_packs = [
                'core',
                'construction',
                'cleaning',
                'physiotherapy',
                'dance',
            ];

            $resource_icon_packs = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'strval',
                            request(
                                'resource_icon_packs',
                                ['core']
                            )
                        ),
                        static fn(string $pack): bool =>
                            in_array(
                                $pack,
                                $allowed_icon_packs,
                                true
                            )
                    )
                )
            );

            if (!in_array('core', $resource_icon_packs, true)) {
                array_unshift($resource_icon_packs, 'core');
            }

            // Validate admin data
            if (empty($admin) || !is_array($admin)) {
                throw new InvalidArgumentException('Invalid admin data provided.');
            }

            // Validate required admin fields
            if (
                empty($admin['first_name']) ||
                empty($admin['last_name']) ||
                empty($admin['email']) ||
                empty($admin['username']) ||
                empty($admin['password'])
            ) {
                throw new InvalidArgumentException('Missing required admin fields.');
            }

            // Validate email format
            if (!filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid admin email format.');
            }

            // Validate username format (alphanumeric, underscore, dash, @, dot)
            if (!preg_match('/^[a-zA-Z0-9_@.\-]{3,50}$/', $admin['username'])) {
                throw new InvalidArgumentException(
                    'Invalid username format. Use 3-50 alphanumeric characters, underscores, dashes, @ or dots.',
                );
            }

            // Validate password strength
            if (strlen($admin['password']) < 8) {
                throw new InvalidArgumentException('Password must be at least 8 characters long.');
            }

            // Validate company data
            if (empty($company) || !is_array($company)) {
                throw new InvalidArgumentException('Invalid company data provided.');
            }

            // Validate required company fields
            if (empty($company['company_name']) || empty($company['company_email'])) {
                throw new InvalidArgumentException('Missing required company fields.');
            }

            // Validate company email format
            if (!filter_var($company['company_email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid company email format.');
            }

            // Validate company link if provided
            if (!empty($company['company_link']) && !filter_var($company['company_link'], FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Invalid company link URL format.');
            }

            // Sanitize string inputs
            $admin['first_name'] = strip_tags(trim($admin['first_name']));
            $admin['last_name'] = strip_tags(trim($admin['last_name']));

            if (!empty($admin['phone_number'])) {
                $admin['phone_number'] = strip_tags(trim($admin['phone_number']));
            }

            $company['company_name'] = strip_tags(trim($company['company_name']));

            $this->instance->migrate();

            // MODUL-Scheduler defaults for German installations.
            if ($admin['language'] === 'german') {
                setting([
                    'default_timezone' => 'Europe/Berlin',
                    'first_weekday' => 'monday',
                    'time_format' => 'military',
                ]);

                date_default_timezone_set('Europe/Berlin');
            }

            // Insert admin
            $admin['timezone'] = date_default_timezone_get();
            $admin['settings']['username'] = $admin['username'];
            $admin['settings']['password'] = $admin['password'];
            $admin['settings']['notifications'] = true;
            $admin['settings']['calendar_view'] = CALENDAR_VIEW_DEFAULT;
            unset($admin['username'], $admin['password']);
            $admin['id'] = $this->admins_model->save($admin);

            session([
                'user_id' => $admin['id'],
                'user_email' => $admin['email'],
                'role_slug' => DB_SLUG_ADMIN,
                'language' => $admin['language'],
                'timezone' => $admin['timezone'],
                'username' => $admin['settings']['username'],
            ]);

            // Save company settings
            setting([
                'company_name' => $company['company_name'],
                'company_email' => $company['company_email'],
                'company_link' => $company['company_link'],
                'enabled_modules' => json_encode($enabled_modules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'resource_icon_packs' => json_encode(
                    $resource_icon_packs,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ),
            ]);

            // Service
            $service_id = $this->services_model->save([
                'name' => 'Service',
                'duration' => '30',
                'price' => '0',
                'currency' => $admin['language'] === 'german' ? 'EUR' : '',
                'slot_interval' => 15,
                'attendants_number' => '1',
            ]);

            // Provider
            $this->providers_model->save([
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.org',
                'phone_number' => '+1 (000) 000-0000',
                'services' => [$service_id],
                'language' => $admin['language'],
                'timezone' => $admin['timezone'],
                'settings' => [
                    'username' => 'janedoe',
                    'password' => random_string(),
                    'working_plan' => setting('company_working_plan'),
                    'notifications' => true,
                    'google_sync' => false,
                    'sync_past_days' => 30,
                    'sync_future_days' => 90,
                    'calendar_view' => CALENDAR_VIEW_DEFAULT,
                ],
            ]);

            // Customer
            $this->customers_model->save([
                'first_name' => 'James',
                'last_name' => 'Doe',
                'email' => 'james@example.org',
                'phone_number' => '+1 (000) 000-0000',
            ]);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
    private function configure_database_namespace(
        array $database
    ): void {
        $mode = (string) (
            $database['mode'] ?? 'prefixed'
        );

        if (
            !in_array(
                $mode,
                ['empty', 'prefixed'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Der Datenbankmodus ist ungültig.'
            );
        }

        $prefix = trim(
            (string) ($database['prefix'] ?? '')
        );

        if ($mode === 'prefixed' && $prefix === '') {
            throw new InvalidArgumentException(
                'Für eine bestehende Datenbank ist ein Tabellenpräfix erforderlich.'
            );
        }

        if (
            $prefix !== '' &&
            !preg_match(
                '/^[A-Za-z][A-Za-z0-9_]{0,30}_?$/',
                $prefix
            )
        ) {
            throw new InvalidArgumentException(
                'Das Tabellenpräfix darf nur Buchstaben, Zahlen und Unterstriche enthalten, muss mit einem Buchstaben beginnen und darf höchstens 32 Zeichen lang sein.'
            );
        }

        if (
            $prefix !== '' &&
            !str_ends_with($prefix, '_')
        ) {
            $prefix .= '_';
        }

        $existing_tables =
            $this->find_namespace_tables($prefix);

        if ($existing_tables) {
            $examples = implode(
                ', ',
                array_slice($existing_tables, 0, 5)
            );

            throw new RuntimeException(
                'Der gewählte Tabellenbereich ist nicht leer. Bereits vorhanden: '
                . $examples
                . '. Bitte verwenden Sie ein anderes Präfix oder eine leere Datenbank.'
            );
        }

        $this->write_database_prefix($prefix);

        // Die aktuelle Anfrage läuft bereits mit einer geöffneten
        // CodeIgniter-Datenbankverbindung. Für die Migration muss
        // deshalb auch die aktive Verbindung umgestellt werden.
        $this->db->dbprefix = $prefix;
    }

    private function find_namespace_tables(
        string $prefix
    ): array {
        $query = $this->db->query(
            'SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME LIKE ?
             ORDER BY TABLE_NAME',
            [
                $this->db->database,
                $prefix === ''
                    ? '%'
                    : $this->escape_like_pattern(
                        $prefix
                    ) . '%',
            ]
        );

        return array_map(
            static fn(array $row): string =>
                (string) $row['TABLE_NAME'],
            $query->result_array()
        );
    }

    private function escape_like_pattern(
        string $value
    ): string {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }

    private function write_database_prefix(
        string $prefix
    ): void {
        $config_path = FCPATH . 'config.php';

        if (!is_file($config_path)) {
            throw new RuntimeException(
                'Die Konfigurationsdatei config.php wurde nicht gefunden.'
            );
        }

        if (!is_writable($config_path)) {
            throw new RuntimeException(
                'Die Konfigurationsdatei config.php ist nicht beschreibbar.'
            );
        }

        $contents = file_get_contents($config_path);

        if ($contents === false) {
            throw new RuntimeException(
                'Die Konfigurationsdatei konnte nicht gelesen werden.'
            );
        }

        $replacement =
            "const DB_PREFIX = '" .
            addslashes($prefix) .
            "';";

        $updated = preg_replace(
            "/const\\s+DB_PREFIX\\s*=\\s*'[^']*';/",
            $replacement,
            $contents,
            1,
            $count
        );

        if (
            $updated === null ||
            $count !== 1
        ) {
            throw new RuntimeException(
                'DB_PREFIX konnte in config.php nicht eindeutig aktualisiert werden.'
            );
        }

        $temporary_path =
            $config_path . '.tmp-' . bin2hex(
                random_bytes(6)
            );

        if (
            file_put_contents(
                $temporary_path,
                $updated,
                LOCK_EX
            ) === false
        ) {
            throw new RuntimeException(
                'Die temporäre Konfigurationsdatei konnte nicht geschrieben werden.'
            );
        }

        if (!rename($temporary_path, $config_path)) {
            @unlink($temporary_path);

            throw new RuntimeException(
                'Die aktualisierte Konfiguration konnte nicht aktiviert werden.'
            );
        }
    }

}
