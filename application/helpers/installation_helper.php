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

const INSTALLATION_STATE_EMPTY = 'empty';
const INSTALLATION_STATE_PARTIAL = 'partial';
const INSTALLATION_STATE_INSTALLED = 'installed';

/**
 * Determine the installation state of the currently configured DB prefix.
 *
 * Tables belonging to any other prefix are ignored automatically by
 * CodeIgniter's configured dbprefix.
 */
function get_installation_state(): string
{
    $CI = &get_instance();

    $required_tables = [
        'roles',
        'users',
        'user_settings',
        'settings',
        'services',
        'appointments',
    ];

    $existing_count = 0;

    foreach ($required_tables as $table) {
        if ($CI->db->table_exists($table)) {
            $existing_count++;
        }
    }

    if ($existing_count === 0) {
        return INSTALLATION_STATE_EMPTY;
    }

    if ($existing_count === count($required_tables)) {
        return INSTALLATION_STATE_INSTALLED;
    }

    return INSTALLATION_STATE_PARTIAL;
}

/**
 * Check whether the currently configured namespace contains
 * a complete MODUL-Scheduler installation.
 */
function is_app_installed(): bool
{
    return get_installation_state() === INSTALLATION_STATE_INSTALLED;
}

/**
 * Check whether installation remnants exist for the current prefix.
 */
function is_app_partially_installed(): bool
{
    return get_installation_state() === INSTALLATION_STATE_PARTIAL;
}
