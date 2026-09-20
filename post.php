<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$slug = trim((string) ($_GET['slug'] ?? $_GET['legacy_slug'] ?? ''));
$post = find_published_post_by_slug($slug);
if (!$post) {
    require ROOT_PATH . '/404.php';
    exit;
}

if (isset($_GET['legacy_slug'])) {
    redirect(post_url($post), 301);
}

$viewKey = 'viewed_post_' . (int) $post['id'];
if (empty($_SESSION[$viewKey])) {
    $statement = db()->prepare('UPDATE posts SET views = views + 1 WHERE id = ?');
    $statement->execute([(int) $post['id']]);
    $_SESSION[$viewKey] = time();
    $post['views']++;
}

$relatedStatement = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='published' AND p.published_at <= NOW() AND p.category_id = ? AND p.id != ? ORDER BY p.published_at DESC LIMIT 3");
$relatedStatement->execute([(int) $post['category_id'], (int) $post['id']]);
$related = $relatedStatement->fetchAll();
$latestStatement = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='published' AND p.published_at <= NOW() AND p.id != ? ORDER BY p.published_at DESC LIMIT 3");
$latestStatement->execute([(int) $post['id']]);
$latest = $latestStatement->fetchAll();

$pageTitle = $post['title'];
$metaDescription = $post['short_description'] ?: excerpt($post['content'], 180);
$canonical = post_url($post);
$ogImage = image_url($post['featured_image']);
$isArticle = true;
require ROOT_PATH . '/includes/header.php';
$shareUrl = rawurlencode($canonical);
$shareTitle = rawurlencode($post['title']);
?>
<main id="main-content" class="article-page">
  <article class="article-story">
    <header class="article-header container">
      <nav class="article-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">முகப்பு</a><span aria-hidden="true">/</span><?php if ($post['category_name']): ?><a href="<?= e(url('category/' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a><?php endif; ?></nav>
      <?php if ($post['category_name']): ?><a class="category-kicker" href="<?= e(url('category/' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a><?php endif; ?>
      <h1><?= e($post['title']) ?></h1>
      <?php if ($post['short_description']): ?><p class="article-deck"><?= e($post['short_description']) ?></p><?php endif; ?>
      <div class="article-meta"><span>வெளியீடு</span><time datetime="<?= e(date('c', strtotime($post['published_at']))) ?>"><?= e(format_date($post['published_at'], 'd F Y, H:i')) ?></time><?php if (strtotime($post['updated_at']) > strtotime($post['published_at']) + 60): ?><span class="meta-divider" aria-hidden="true">•</span><span>புதுப்பிப்பு <?= e(format_date($post['updated_at'], 'd F Y, H:i')) ?></span><?php endif; ?><span class="meta-divider" aria-hidden="true">•</span><span><?= reading_time($post['content']) ?> நிமிட வாசிப்பு</span><span class="meta-divider" aria-hidden="true">•</span><span><?= number_format((int) $post['views']) ?> பார்வைகள்</span></div>
      <div class="article-tools" aria-label="வாசிப்பு அமைப்புகள்"><span>எழுத்தளவு</span><button type="button" data-text-size="decrease" aria-label="எழுத்தளவைக் குறைக்க">A−</button><button type="button" data-text-size="reset" aria-label="இயல்பான எழுத்தளவு">A</button><button type="button" data-text-size="increase" aria-label="எழுத்தளவை அதிகரிக்க">A+</button><button type="button" data-print-article aria-label="கட்டுரையை அச்சிடுக">அச்சிடுக</button></div>
    </header>
    <?php if ($post['featured_image']): ?><figure class="article-cover"><div class="article-cover-frame"><img src="<?= e(image_url($post['featured_image'])) ?>" alt="<?= e($post['image_alt'] ?: $post['title']) ?>" decoding="async" fetchpriority="high"></div></figure><?php endif; ?>
    <div class="container article-layout">
      <div class="article-content"><?= sanitize_html($post['content']) ?></div>
      <aside class="article-sidebar"><section class="share-panel"><span class="sidebar-label">SHARE</span><strong>இந்தக் கட்டுரையைப் பகிருங்கள்</strong><div class="share-links"><a target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>">Facebook</a><a target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>">X / Twitter</a><a target="_blank" rel="noopener noreferrer" href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>">WhatsApp</a><button type="button" data-copy-link data-url="<?= e($canonical) ?>">இணைப்பை நகலெடு</button></div><span class="copy-status" data-copy-status aria-live="polite"></span></section><?php if ($latest): ?><section class="article-latest" aria-labelledby="article-latest-title"><span class="sidebar-label">LATEST</span><h2 id="article-latest-title">சமீபத்தியவை</h2><?php foreach ($latest as $item): ?><article><a href="<?= e(post_url($item)) ?>"><?= e($item['title']) ?></a><time datetime="<?= e(date('c', strtotime($item['published_at']))) ?>"><?= e(format_date($item['published_at'], 'H:i')) ?></time></article><?php endforeach; ?></section><?php endif; ?></aside>
    </div>
  </article>
  <?php if ($related): ?><section class="section-block container"><div class="section-heading"><h2>தொடர்புடைய செய்திகள்</h2></div><div class="row g-4"><?php foreach ($related as $post): ?><div class="col-md-4"><?php require ROOT_PATH . '/includes/article-card.php'; ?></div><?php endforeach; ?></div></section><?php endif; ?>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
