<?php

declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/admin/';

require dirname(__DIR__) . '/config/config.php';

$_SESSION['admin'] = [
    'id' => 1,
    'name' => 'Render Test',
    'username' => 'render-test',
];

$templates = [
    'admin/index.php',
    'admin/posts/index.php',
    'admin/posts/edit.php',
    'admin/categories/index.php',
    'admin/settings/index.php',
];

foreach ($templates as $template) {
    $_GET = [];
    $_POST = [];
    ob_start();
    include ROOT_PATH . '/' . $template;
    $html = (string) ob_get_clean();

    foreach (['admin-shell', 'admin-page-head', 'csrf_token'] as $marker) {
        if (!str_contains($html, $marker)) {
            throw new RuntimeException($template . ' is missing rendered marker: ' . $marker);
        }
    }
}

echo "Admin template render checks passed.\n";
