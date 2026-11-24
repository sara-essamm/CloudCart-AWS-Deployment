<?php
/**
 * config.php - reads settings from the .env file / environment variables.
 *
 * Locally: put your settings in the ".env" file (copy .env.example).
 * On AWS:  set real environment variables (or keep a .env file on each server).
 * Real environment variables always win over the .env file.
 */

function load_env(string $file): void
{
    if (!is_file($file)) {
        return;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            // Quoted value: take what is inside the quotes
            $quote = $value[0];
            $end = strpos($value, $quote, 1);
            $value = ($end !== false) ? substr($value, 1, $end - 1) : substr($value, 1);
        } else {
            // Unquoted value: remove a trailing "  # comment"
            $value = trim(preg_replace('/\s+#.*$/', '', $value));
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

/** Get a setting, or a default value if it is not set. */
function env(string $key, $default = null)
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

load_env(dirname(__DIR__) . '/.env');

define('APP_DEBUG', strtolower((string) env('APP_DEBUG', 'false')) === 'true');
define('BEHIND_PROXY', strtolower((string) env('BEHIND_PROXY', 'false')) === 'true');
define('DEFAULT_IMAGE', 'images/placeholder.svg');

// Show PHP errors on screen only while developing.
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);
