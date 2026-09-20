<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/xml; charset=UTF-8');

$posts = db()->query("SELECT slug, updated_at FROM posts WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC")->fetchAll();
$categories = db()->query("SELECT slug, updated_at FROM categories WHERE status = 'active' ORDER BY sort_order")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc><?= e(url()) ?></loc><changefreq>daily</changefreq><priority>1.0</priority></url>
<?php foreach ($categories as $category): ?>
  <url><loc><?= e(url('category/' . rawurlencode($category['slug']))) ?></loc><lastmod><?= e(date('c', strtotime($category['updated_at']))) ?></lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>
<?php endforeach; ?>
<?php foreach ($posts as $post): ?>
  <url><loc><?= e(url('news/' . rawurlencode($post['slug']))) ?></loc><lastmod><?= e(date('c', strtotime($post['updated_at']))) ?></lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>
<?php endforeach; ?>
</urlset>

