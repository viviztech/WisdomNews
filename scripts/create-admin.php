<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/config.php';

$options = getopt('', ['name:', 'username:', 'email:', 'password::']);
$name = trim((string) ($options['name'] ?? 'Administrator'));
$username = trim((string) ($options['username'] ?? 'admin'));
$email = trim((string) ($options['email'] ?? ''));
$password = (string) ($options['password'] ?? env('ADMIN_PASSWORD', ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "A valid --email is required.\n");
    exit(1);
}
if ($password === '') {
    fwrite(STDOUT, 'Password: ');
    $password = trim((string) fgets(STDIN));
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Use a password with at least 12 characters.\n");
    exit(1);
}

$statement = db()->prepare('INSERT INTO admins (name,username,email,password,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),password=VALUES(password),updated_at=NOW()');
$statement->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, "Administrator account created or updated.\n");

