<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$query = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$posts = [];
$total = 0;
if ($query !== '') {
    $term = '%' . $query . '%';
    $count = db()->prepare("SELECT COUNT(*) FROM posts WHERE status='published' AND published_at <= NOW() AND (title LIKE ? OR short_description LIKE ? OR content LIKE ?)");
    $count->execute([$term, $term, $term]);
    $total = (int) $count->fetchColumn();
    $statement = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='published' AND p.published_at <= NOW() AND (p.title LIKE :title OR p.short_description LIKE :description OR p.content LIKE :content) ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset");
    $statement->bindValue(':title', $term);
    $statement->bindValue(':description', $term);
    $statement->bindValue(':content', $term);
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
    $statement->execute();
    $posts = $statement->fetchAll();
}
$pages = max(1, (int) ceil($total / $perPage));
$pageTitle = $query ? 'தேடல்: ' . $query : 'செய்திகளைத் தேடுங்கள்';
$metaDescription = $query ? $query . ' தொடர்பான Wisdom News தேடல் முடிவுகள்.' : 'Wisdom News செய்திகளைத் தேடுங்கள்.';
$canonical = url('search') . ($query ? '?q=' . rawurlencode($query) : '');
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">
  <header class="page-hero search-hero"><div class="container"><span class="page-kicker">WISDOM NEWS ARCHIVE</span><h1>செய்திகளைத் தேடுங்கள்</h1><p>தலைப்பு, சுருக்கம் அல்லது கட்டுரையின் உள்ளடக்கத்தில் தேடலாம்.</p><form class="search-form-large" action="<?= e(url('search')) ?>" method="get"><label class="visually-hidden" for="search-q">செய்திகளைத் தேடுங்கள்</label><input id="search-q" type="search" name="q" value="<?= e($query) ?>" placeholder="தேட வேண்டிய சொல்லை உள்ளிடுங்கள்…" required><button class="btn btn-brand" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.4-4.4m2.4-5.35a7.75 7.75 0 1 1-15.5 0 7.75 7.75 0 0 1 15.5 0Z"/></svg><span>தேடு</span></button></form></div></header>
  <section class="section-block container">
    <?php if ($query): ?><div class="section-heading search-heading"><div><span class="section-label">SEARCH RESULTS</span><h2>“<?= e($query) ?>”</h2></div><span class="result-count"><?= number_format($total) ?> முடிவுகள்</span></div><?php endif; ?>
    <?php if ($posts): ?><div class="search-results"><?php foreach ($posts as $post): ?><article class="search-result"><a class="search-result-media" href="<?= e(post_url($post)) ?>"><img src="<?= e(image_url($post['featured_image'] ?? '')) ?>" alt="<?= e($post['image_alt'] ?: $post['title']) ?>" loading="lazy"></a><div><a class="category-kicker" href="<?= e(url('category/' . rawurlencode($post['category_slug'] ?? ''))) ?>"><?= e($post['category_name'] ?? '') ?></a><h3><a href="<?= e(post_url($post)) ?>"><?= e($post['title']) ?></a></h3><p><?= e($post['short_description'] ?: excerpt($post['content'])) ?></p><div class="story-meta"><time datetime="<?= e(date('c', strtotime($post['published_at']))) ?>"><?= e(format_date($post['published_at'])) ?></time><span><?= reading_time($post['content']) ?> நிமிட வாசிப்பு</span></div><a class="inline-read-more" href="<?= e(post_url($post)) ?>">முழுவதும் படிக்க <span aria-hidden="true">→</span></a></div></article><?php endforeach; ?></div><?= pagination($page, $pages, url('search?q=' . rawurlencode($query))) ?>
    <?php elseif ($query): ?><div class="empty-state"><span class="empty-state-mark" aria-hidden="true">?</span><span class="section-label">NO RESULTS</span><h2>முடிவுகள் கிடைக்கவில்லை</h2><p>வேறு சொல்லைப் பயன்படுத்தி மீண்டும் தேடிப் பாருங்கள்.</p></div>
    <?php else: ?><div class="search-prompt"><span aria-hidden="true">⌕</span><p>தலைப்பு, சுருக்கம் மற்றும் கட்டுரை உள்ளடக்கத்தில் தேடலாம்.</p></div><?php endif; ?>
  </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
