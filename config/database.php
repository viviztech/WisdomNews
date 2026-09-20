<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        env('DB_HOST', 'localhost'),
        env('DB_PORT', '3306'),
        env('DB_NAME', 'wisdomnews')
    );

    try {
        $pdo = new PDO($dsn, (string) env('DB_USER', 'root'), (string) env('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        if (APP_DEBUG && PHP_SAPI === 'cli') {
            throw $exception;
        }
        http_response_code(503);
        exit('Database connection is unavailable. Check the local .env database settings.');
    }

    return $pdo;
}
