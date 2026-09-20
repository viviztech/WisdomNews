<?php

declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if (preg_match('#^/category/([^/]+)/?$#u', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/category.php';
    return true;
}

if (preg_match('#^/news/([^/]+)/?$#u', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/post.php';
    return true;
}

if ($path === '/search' || $path === '/search/') {
    require __DIR__ . '/search.php';
    return true;
}

if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}

if (str_starts_with($path, '/admin')) {
    $adminFile = __DIR__ . rtrim($path, '/');
    if (is_dir($adminFile)) {
        $adminFile .= '/index.php';
    }
    if (is_file($adminFile)) {
        require $adminFile;
        return true;
    }
}

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

$_GET['legacy_slug'] = trim($path, '/');
require __DIR__ . '/post.php';
return true;

