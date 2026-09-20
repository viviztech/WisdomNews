<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$statement = db()->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
$statement->execute([$slug]);
$category = $statement->fetch();
if (!$category) {
    require ROOT_PATH . '/404.php';
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$count = db()->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ? AND status = 'published' AND published_at <= NOW()");
$count->execute([(int) $category['id']]);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
if ($page > $pages && $total > 0) {
    require ROOT_PATH . '/404.php';
    exit;
}
$statement = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.category_id = :category AND p.status='published' AND p.published_at <= NOW() ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset");
$statement->bindValue(':category', (int) $category['id'], PDO::PARAM_INT);
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$posts = $statement->fetchAll();
$featuredPost = $posts[0] ?? null;
$listPosts = $featuredPost ? array_slice($posts, 1) : [];
$popularStatement = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.category_id=? AND p.status='published' AND p.published_at <= NOW() ORDER BY p.views DESC, p.published_at DESC LIMIT 5");
$popularStatement->execute([(int) $category['id']]);
$popularPosts = $popularStatement->fetchAll();

$pageTitle = $category['name'];
$metaDescription = $category['description'] ?: $category['name'] . ' தொடர்பான சமீபத்திய செய்திகள் மற்றும் கட்டுரைகள்.';
$canonical = category_url($category) . ($page > 1 ? '?page=' . $page : '');
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">
  <header class="page-hero category-page-hero <?= ($category['slug'] ?? '') === 'wisdom-special' ? 'category-page-hero--special' : '' ?>"><div class="container"><span class="page-kicker">செய்திப் பிரிவு</span><h1><?= e($category['name']) ?></h1><?php if ($category['description']): ?><p><?= e($category['description']) ?></p><?php endif; ?><span class="page-count"><?= number_format($total) ?> கட்டுரைகள்</span></div></header>
  <section class="section-block container">
    <?php if ($featuredPost): ?>
      <article class="category-lead"><a class="category-lead-media" href="<?= e(post_url($featuredPost)) ?>"><img src="<?= e(image_url($featuredPost['featured_image'] ?? '')) ?>" alt="<?= e($featuredPost['image_alt'] ?: $featuredPost['title']) ?>" fetchpriority="high"></a><div class="category-lead-content"><span class="category-kicker">முக்கிய செய்தி</span><h2><a href="<?= e(post_url($featuredPost)) ?>"><?= e($featuredPost['title']) ?></a></h2><p><?= e($featuredPost['short_description'] ?: excerpt($featuredPost['content'], 220)) ?></p><div class="story-meta"><time datetime="<?= e(date('c', strtotime($featuredPost['published_at']))) ?>"><?= e(format_date($featuredPost['published_at'], 'd M Y, H:i')) ?></time><span><?= reading_time($featuredPost['content']) ?> நிமிட வாசிப்பு</span></div><a class="inline-read-more" href="<?= e(post_url($featuredPost)) ?>">முழுவதும் படிக்க <span aria-hidden="true">→</span></a></div></article>
      <?php if ($listPosts || $popularPosts): ?><div class="listing-layout <?= !$listPosts ? 'listing-layout-ranking-only' : '' ?>"><?php if ($listPosts): ?><div><div class="section-heading compact-heading"><div><span class="section-label">LATEST</span><h2>மேலும் செய்திகள்</h2></div></div><div class="listing-grid"><?php foreach ($listPosts as $post): ?><div><?php require ROOT_PATH . '/includes/article-card.php'; ?></div><?php endforeach; ?></div></div><?php endif; ?><?php if ($popularPosts): ?><aside class="ranking-panel category-ranking" aria-labelledby="category-popular"><div class="ranking-header"><span>POPULAR</span><h2 id="category-popular">அதிகம் படிக்கப்பட்டவை</h2></div><ol><?php foreach ($popularPosts as $popular): ?><li><a href="<?= e(post_url($popular)) ?>"><img class="ranking-thumb" src="<?= e(image_url($popular['featured_image'] ?? '')) ?>" alt="" loading="lazy"><span class="ranking-copy"><strong><?= e($popular['title']) ?></strong><small><?= number_format((int) $popular['views']) ?> பார்வைகள்</small></span></a></li><?php endforeach; ?></ol></aside><?php endif; ?></div><?php endif; ?><?= pagination($page, $pages, category_url($category)) ?>
    <?php else: ?><div class="empty-state"><span class="empty-state-mark" aria-hidden="true">W</span><span class="section-label">COMING SOON</span><h2>இன்னும் செய்திகள் இல்லை</h2><p>இந்தப் பிரிவில் புதிய கட்டுரைகள் விரைவில் வெளியாகும்.</p><a class="outline-action" href="<?= e(url()) ?>">முகப்புக்குச் செல்லுங்கள் <span aria-hidden="true">→</span></a></div><?php endif; ?>
  </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
