<?php
/**
 * These functions are used for testing Laravel command.
 */

if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        if ($default)
            return $default;
        return false;
    }
}

if (!function_exists('resource_path')) {
    function resource_path($path = '')
    {
        return __DIR__ . '/../test-resources/resources/' . $path;
    }
}

if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return __DIR__ . '/../test-resources/exploration_files/' . $path;
    }
}

if (!function_exists('trans')) {
    function trans($key = null, $replace = [], $locale = null)
    {
        $path = resource_path('lang/' . $locale . '/' . $key . '.php');
        if (is_file($path))
            return include $path;
        return [];
    }
}

function deleteAll($path)
{
    if (is_file($path)) {
        return unlink($path);
    } elseif (is_dir($path)) {
        $scan = glob(rtrim($path, '/') . '/*');
        foreach ($scan as $index => $p) {
            deleteAll($p);
        }
        return @rmdir($path);
    }
}
