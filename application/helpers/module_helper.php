<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('enabled_modules')) {
    function enabled_modules(): array
    {
        $raw = setting('enabled_modules');
        $modules = json_decode((string) $raw, true);

        if (!is_array($modules)) {
            $modules = ['scheduler'];
        }

        $modules = array_values(array_unique(array_map('strval', $modules)));

        if (!in_array('scheduler', $modules, true)) {
            array_unshift($modules, 'scheduler');
        }

        return $modules;
    }
}

if (!function_exists('module_enabled')) {
    function module_enabled(string $module): bool
    {
        return in_array($module, enabled_modules(), true);
    }
}

if (!function_exists('require_module')) {
    function require_module(string $module): void
    {
        if (!module_enabled($module)) {
            abort(404, 'Not Found');
        }
    }
}
