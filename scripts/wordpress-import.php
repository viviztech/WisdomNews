<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/config.php';

$prefix = (string) env('WP_TABLE_PREFIX', 'wp_');
if (!preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
    fwrite(STDERR, "WP_TABLE_PREFIX contains invalid characters.\n");
    exit(1);
}

$wpDsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    env('WP_DB_HOST', 'localhost'),
    env('WP_DB_PORT', '3306'),
    env('WP_DB_NAME', '')
);
if ((string) env('WP_DB_NAME', '') === '') {
    fwrite(STDERR, "Set WP_DB_NAME and the other WP_DB_* values before importing.\n");
    exit(1);
}

try {
    $wp = new PDO($wpDsn, (string) env('WP_DB_USER', 'root'), (string) env('WP_DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not connect to WordPress: {$exception->getMessage()}\n");
    exit(1);
}

$target = db();
$uploadsSource = rtrim(str_replace('\\', '/', (string) env('WP_UPLOADS_PATH', '')), '/');
$wpUploadsUrl = rtrim((string) env('WP_UPLOADS_URL', ''), '/');
$targetUploadsPath = ROOT_PATH . '/uploads/wordpress';
$targetUploadsUrl = url('uploads/wordpress');

function copy_tree(string $source, string $destination): int
{
    if (!is_dir($source)) {
        return 0;
    }
    if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
        throw new RuntimeException('Could not create ' . $destination);
    }
    $copied = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $item) {
        $relative = substr(str_replace('\\', '/', $item->getPathname()), strlen($source) + 1);
        $destinationPath = $destination . '/' . $relative;
        if ($item->isDir()) {
            if (!is_dir($destinationPath)) mkdir($destinationPath, 0755, true);
        } elseif (!is_file($destinationPath)) {
            if (!is_dir(dirname($destinationPath))) mkdir(dirname($destinationPath), 0755, true);
            if (copy($item->getPathname(), $destinationPath)) $copied++;
        }
    }
    return $copied;
}

$target->beginTransaction();
try {
    $categoryRows = $wp->query(
        "SELECT tt.term_taxonomy_id,tt.parent,t.term_id,t.name,t.slug,tt.description
         FROM {$prefix}term_taxonomy tt
         JOIN {$prefix}terms t ON t.term_id=tt.term_id
         WHERE tt.taxonomy='category'
         ORDER BY tt.parent,t.name"
    )->fetchAll();
    $categoryMap = [];
    $categoryUpsert = $target->prepare(
        "INSERT INTO categories (wordpress_id,parent_id,name,slug,description,status,sort_order,created_at,updated_at)
         VALUES (?,NULL,?,?,?,'active',0,NOW(),NOW())
         ON DUPLICATE KEY UPDATE name=VALUES(name),slug=VALUES(slug),description=VALUES(description),updated_at=NOW()"
    );
    foreach ($categoryRows as $row) {
        $existing = $target->prepare('SELECT id,slug FROM categories WHERE wordpress_id=?');
        $existing->execute([(int) $row['term_id']]);
        $found = $existing->fetch();
        $slug = $found ? $found['slug'] : unique_slug('categories', $row['slug'] ?: $row['name']);
        $categoryUpsert->execute([(int) $row['term_id'], $row['name'], $slug, $row['description']]);
        $lookup = $target->prepare('SELECT id FROM categories WHERE wordpress_id=?');
        $lookup->execute([(int) $row['term_id']]);
        $categoryMap[(int) $row['term_id']] = (int) $lookup->fetchColumn();
    }
    $parentUpdate = $target->prepare('UPDATE categories SET parent_id=? WHERE wordpress_id=?');
    foreach ($categoryRows as $row) {
        $parentUpdate->execute([$categoryMap[(int) $row['parent']] ?? null, (int) $row['term_id']]);
    }

    $posts = $wp->query(
        "SELECT p.ID,p.post_title,p.post_name,p.post_excerpt,p.post_content,p.post_status,p.post_date,p.post_modified,
                thumb.guid featured_image,alt.meta_value image_alt,
                (SELECT t.term_id FROM {$prefix}term_relationships tr
                 JOIN {$prefix}term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='category'
                 JOIN {$prefix}terms t ON t.term_id=tt.term_id
                 WHERE tr.object_id=p.ID ORDER BY tr.term_order,tt.term_taxonomy_id LIMIT 1) category_wp_id
         FROM {$prefix}posts p
         LEFT JOIN {$prefix}postmeta pm ON pm.post_id=p.ID AND pm.meta_key='_thumbnail_id'
         LEFT JOIN {$prefix}posts thumb ON thumb.ID=CAST(pm.meta_value AS UNSIGNED)
         LEFT JOIN {$prefix}postmeta alt ON alt.post_id=thumb.ID AND alt.meta_key='_wp_attachment_image_alt'
         WHERE p.post_type='post' AND p.post_status IN ('publish','draft')
         ORDER BY p.ID"
    )->fetchAll();

    $upsert = $target->prepare(
        "INSERT INTO posts (wordpress_id,category_id,title,slug,short_description,content,featured_image,image_alt,status,views,published_at,created_at,updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,0,?,?,?)
         ON DUPLICATE KEY UPDATE category_id=VALUES(category_id),title=VALUES(title),slug=VALUES(slug),
         short_description=VALUES(short_description),content=VALUES(content),featured_image=VALUES(featured_image),
         image_alt=VALUES(image_alt),status=VALUES(status),published_at=VALUES(published_at),updated_at=VALUES(updated_at)"
    );
    foreach ($posts as $row) {
        $existing = $target->prepare('SELECT id,slug FROM posts WHERE wordpress_id=?');
        $existing->execute([(int) $row['ID']]);
        $found = $existing->fetch();
        $slug = $found ? $found['slug'] : unique_slug('posts', $row['post_name'] ?: $row['post_title']);
        $content = (string) $row['post_content'];
        $featured = (string) ($row['featured_image'] ?? '');
        if ($wpUploadsUrl !== '') {
            $content = str_replace($wpUploadsUrl, $targetUploadsUrl, $content);
            $featured = str_replace($wpUploadsUrl, $targetUploadsUrl, $featured);
        }
        $upsert->execute([
            (int) $row['ID'],
            $categoryMap[(int) $row['category_wp_id']] ?? null,
            $row['post_title'],
            $slug,
            $row['post_excerpt'] ?: excerpt($content, 260),
            $content,
            $featured ?: null,
            $row['image_alt'] ?: $row['post_title'],
            $row['post_status'] === 'publish' ? 'published' : 'draft',
            $row['post_date'],
            $row['post_date'],
            $row['post_modified'],
        ]);
    }
    $target->commit();

    $copied = $uploadsSource !== '' ? copy_tree($uploadsSource, $targetUploadsPath) : 0;
    $categoryCount = (int) $target->query('SELECT COUNT(*) FROM categories WHERE wordpress_id IS NOT NULL')->fetchColumn();
    $postCount = (int) $target->query('SELECT COUNT(*) FROM posts WHERE wordpress_id IS NOT NULL')->fetchColumn();
    fwrite(STDOUT, "Import complete.\nCategories imported: {$categoryCount}\nPosts imported: {$postCount}\nImages copied: {$copied}\n");
    $samples = $target->query('SELECT wordpress_id,title,slug FROM posts WHERE wordpress_id IS NOT NULL ORDER BY published_at DESC LIMIT 3')->fetchAll();
    foreach ($samples as $sample) {
        fwrite(STDOUT, sprintf("Sample: WP #%d | %s | %s\n", $sample['wordpress_id'], $sample['title'], post_url($sample)));
    }
} catch (Throwable $exception) {
    if ($target->inTransaction()) $target->rollBack();
    fwrite(STDERR, "Import failed and was rolled back: {$exception->getMessage()}\n");
    exit(1);
}
