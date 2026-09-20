<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return match (strtolower($value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

load_env(ROOT_PATH . '/.env');

define('APP_NAME', (string) env('APP_NAME', 'Wisdom News'));
define('APP_URL', rtrim((string) env('APP_URL', 'http://localhost/wisdom'), '/'));
define('APP_ENV', (string) env('APP_ENV', 'production'));
define('APP_DEBUG', (bool) env('APP_DEBUG', false));
define('MAX_UPLOAD_BYTES', max(1, (int) env('MAX_UPLOAD_MB', 5)) * 1024 * 1024);

date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Kolkata'));
ini_set('default_charset', 'UTF-8');
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('wisdomnews_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (bool) env('SESSION_SECURE', false),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
