<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return APP_URL . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function asset(string $path): string
{
    return url('public/assets/' . ltrim($path, '/'));
}

function versioned_asset(string $path): string
{
    $relativePath = ltrim($path, '/');
    $file = ROOT_PATH . '/public/assets/' . $relativePath;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return asset($relativePath) . '?v=' . rawurlencode($version);
}

function redirect(string $target, int $status = 302): never
{
    header('Location: ' . $target, true, $status);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function admin_user(): ?array
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

function require_admin(): void
{
    if (!admin_user()) {
        $return = $_SERVER['REQUEST_URI'] ?? url('admin/');
        redirect(url('admin/login.php?return=' . rawurlencode($return)));
    }
}

function setting(string $key, string $default = ''): string
{
    static $settings = null;
    if ($settings === null) {
        try {
            $settings = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable) {
            $settings = [];
        }
    }
    return (string) ($settings[$key] ?? $default);
}

function active_categories(bool $tree = false): array
{
    $rows = db()->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name")->fetchAll();
    if (!$tree) {
        return $rows;
    }
    $children = [];
    foreach ($rows as $row) {
        $children[(int) ($row['parent_id'] ?? 0)][] = $row;
    }
    return $children;
}

function text_lower(string $text): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
}

function text_length(string $text): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($text, 'UTF-8');
    }
    preg_match_all('/./us', $text, $characters);
    return count($characters[0]);
}

function text_slice(string $text, int $start, int $length): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, $start, $length, 'UTF-8');
    }
    preg_match_all('/./us', $text, $characters);
    return implode('', array_slice($characters[0], $start, $length));
}

function slugify(string $text): string
{
    $text = trim(text_lower($text));
    $text = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $text) ?? '';
    return trim($text, '-') ?: 'news-' . date('Ymd-His');
}

function unique_slug(string $table, string $slug, ?int $ignoreId = null): string
{
    if (!in_array($table, ['posts', 'categories'], true)) {
        throw new InvalidArgumentException('Invalid slug table.');
    }
    $base = slugify($slug);
    $candidate = $base;
    $number = 2;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?" . ($ignoreId ? ' AND id != ?' : '') . ' LIMIT 1';
        $params = $ignoreId ? [$candidate, $ignoreId] : [$candidate];
        $statement = db()->prepare($sql);
        $statement->execute($params);
        if (!$statement->fetch()) {
            return $candidate;
        }
        $candidate = $base . '-' . $number++;
    }
}

function excerpt(string $html, int $length = 170): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
    return text_length($text) <= $length ? $text : rtrim(text_slice($text, 0, $length - 1)) . '…';
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '';
}

function reading_time(string $html): int
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
    if ($text === '') {
        return 1;
    }

    // Tamil text is not reliably separated into words in every article, so the
    // character count provides a steadier estimate alongside the word count.
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $byWords = count($words) / 180;
    $byCharacters = text_length($text) / 900;
    return max(1, (int) ceil(max($byWords, $byCharacters)));
}

function post_url(array $post): string
{
    return url('news/' . rawurlencode((string) $post['slug']));
}

function category_url(array $category): string
{
    return url('category/' . rawurlencode((string) $category['slug']));
}

function image_url(?string $path): string
{
    if (!$path) {
        return asset('images/news-placeholder.svg');
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return url(ltrim($path, '/'));
}

function sanitize_html(string $html): string
{
    $allowed = '<p><br><h2><h3><h4><strong><b><em><i><u><a><ul><ol><li><blockquote><figure><figcaption><img><hr>';
    $html = strip_tags($html, $allowed);
    if (!class_exists('DOMDocument')) {
        return $html;
    }
    $document = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8" ?><!doctype html><html><body><div id="content-root">' . $html . '</div></body></html>');
    libxml_clear_errors();
    $xpath = new DOMXPath($document);
    $root = $xpath->query('//*[@id="content-root"]')?->item(0);
    foreach ($xpath->query('//*[@*]') ?: [] as $element) {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);
            $permitted = in_array($name, ['href', 'src', 'alt', 'title', 'width', 'height', 'loading'], true);
            if (!$permitted || (in_array($name, ['href', 'src'], true) && preg_match('#^(javascript|data):#i', $value))) {
                $element->removeAttribute($attribute->name);
            }
        }
        if ($element->tagName === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
        if ($element->tagName === 'img') {
            $element->setAttribute('loading', 'lazy');
        }
    }
    $clean = '';
    if ($root) {
        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }
    }
    return $clean;
}

function upload_image(array $file, string $folder = 'posts'): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a valid image file.');
    }
    if (($file['size'] ?? 0) < 1 || $file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('The image exceeds the allowed upload size.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    } elseif (function_exists('getimagesize')) {
        $imageInfo = @getimagesize($file['tmp_name']);
        $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    } elseif (function_exists('mime_content_type')) {
        $mime = (string) mime_content_type($file['tmp_name']);
    } else {
        throw new RuntimeException('Image validation is unavailable on this server. Enable the PHP Fileinfo extension.');
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!isset($types[$mime]) || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }
    $folder = in_array($folder, ['posts', 'branding'], true) ? $folder : 'posts';
    $directory = ROOT_PATH . '/uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The upload directory is not writable.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('The image could not be saved.');
    }
    return 'uploads/' . $folder . '/' . $filename;
}

function pagination(int $page, int $pages, string $baseUrl): string
{
    if ($pages <= 1) {
        return '';
    }
    $joiner = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center">';
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) {
        $html .= '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="' . e($baseUrl . $joiner . 'page=' . $i) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}

function find_published_post_by_slug(string $slug): ?array
{
    $statement = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.status = 'published' AND p.published_at <= NOW() LIMIT 1");
    $statement->execute([$slug]);
    return $statement->fetch() ?: null;
}
