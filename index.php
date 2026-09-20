<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (isset($_GET['p']) && ctype_digit((string) $_GET['p'])) {
    $statement = db()->prepare("SELECT slug FROM posts WHERE wordpress_id = ? AND status = 'published' LIMIT 1");
    $statement->execute([(int) $_GET['p']]);
    if ($legacy = $statement->fetch()) {
        redirect(url('news/' . rawurlencode($legacy['slug'])), 301);
    }
    require ROOT_PATH . '/404.php';
    exit;
}

if (isset($_GET['cat']) && ctype_digit((string) $_GET['cat'])) {
    $statement = db()->prepare("SELECT slug FROM categories WHERE wordpress_id = ? AND status = 'active' LIMIT 1");
    $statement->execute([(int) $_GET['cat']]);
    if ($legacy = $statement->fetch()) {
        redirect(url('category/' . rawurlencode($legacy['slug'])), 301);
    }
    require ROOT_PATH . '/404.php';
    exit;
}

if (isset($_GET['s'])) {
    redirect(url('search?q=' . rawurlencode((string) $_GET['s'])), 301);
}

$postSelect = "SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='published' AND p.published_at <= NOW()";
$topStory = db()->query($postSelect . ' ORDER BY p.published_at DESC LIMIT 1')->fetch() ?: null;
$sideStories = db()->query($postSelect . ($topStory ? ' AND p.id != ' . (int) $topStory['id'] : '') . ' ORDER BY p.published_at DESC LIMIT 4')->fetchAll();
$breakingStories = db()->query($postSelect . ' ORDER BY p.published_at DESC LIMIT 6')->fetchAll();
$latest = db()->query($postSelect . ' ORDER BY p.published_at DESC LIMIT 8 OFFSET 5')->fetchAll();
$mostRead = db()->query($postSelect . ($topStory ? ' AND p.id != ' . (int) $topStory['id'] : '') . ' ORDER BY p.views DESC, p.published_at DESC LIMIT 5')->fetchAll();
$categories = active_categories();
$categoryCountRows = db()->query("SELECT category_id, COUNT(*) AS total FROM posts WHERE status='published' AND published_at <= NOW() GROUP BY category_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$sections = [];
$sectionStatement = db()->prepare($postSelect . ' AND p.category_id = ? ORDER BY p.published_at DESC LIMIT 4');
foreach ($categories as $category) {
    $sectionStatement->execute([(int) $category['id']]);
    $posts = $sectionStatement->fetchAll();
    if ($posts) {
        $sections[] = ['category' => $category, 'posts' => $posts];
    }
}

$pageTitle = setting('site_name', APP_NAME);
$metaDescription = setting('footer_text', 'Wisdom News — தமிழ் செய்திகள், ஆழமான பார்வைகள் மற்றும் வாழ்வியல் கட்டுரைகள்.');
require ROOT_PATH . '/includes/header.php';
?>
<main id="main-content">
<?php if ($topStory): ?>
  <div class="breaking-strip" aria-label="தற்போதைய செய்திகள்"><div class="container breaking-inner"><span class="breaking-label"><span aria-hidden="true"></span> தற்போதைய செய்தி</span><div class="breaking-viewport"><div class="breaking-track"><?php foreach ($breakingStories as $breaking): ?><a class="breaking-link" href="<?= e(post_url($breaking)) ?>"><?= e($breaking['title']) ?></a><span aria-hidden="true">◆</span><?php endforeach; ?><?php foreach ($breakingStories as $breaking): ?><a class="breaking-link ticker-copy" href="<?= e(post_url($breaking)) ?>" aria-hidden="true" tabindex="-1"><?= e($breaking['title']) ?></a><span class="ticker-copy" aria-hidden="true">◆</span><?php endforeach; ?></div></div><time class="breaking-time" datetime="<?= e(date('c', strtotime($topStory['published_at']))) ?>"><?= e(format_date($topStory['published_at'], 'H:i')) ?></time></div></div>
  <section class="hero container">
    <div class="eyebrow-heading"><span>இன்றைய முக்கிய செய்திகள்</span></div>
    <div class="hero-grid <?= !$sideStories ? 'hero-grid-single' : '' ?>">
      <article class="lead-story">
        <a class="lead-media" href="<?= e(post_url($topStory)) ?>"><img src="<?= e(image_url($topStory['featured_image'])) ?>" alt="<?= e($topStory['image_alt'] ?: $topStory['title']) ?>" fetchpriority="high"></a>
        <div class="lead-content">
          <?php if ($topStory['category_name']): ?><a class="category-kicker" href="<?= e(url('category/' . rawurlencode($topStory['category_slug']))) ?>"><?= e($topStory['category_name']) ?></a><?php endif; ?>
          <h1><a href="<?= e(post_url($topStory)) ?>"><?= e($topStory['title']) ?></a></h1>
          <p><?= e($topStory['short_description'] ?: excerpt($topStory['content'], 220)) ?></p>
          <div class="lead-meta"><div><time datetime="<?= e(date('c', strtotime($topStory['published_at']))) ?>"><?= e(format_date($topStory['published_at'], 'd M Y, H:i')) ?></time><span><?= reading_time($topStory['content']) ?> நிமிட வாசிப்பு</span></div><a class="hero-cta" href="<?= e(post_url($topStory)) ?>">முழுவதும் படிக்க <span aria-hidden="true">→</span></a></div>
        </div>
      </article>
      <?php if ($sideStories): ?><aside class="hero-side" aria-label="சமீபத்திய செய்திகள்">
        <h2 class="hero-side-title">சமீபத்தியவை</h2>
        <?php foreach ($sideStories as $story): ?>
          <article class="compact-story"><a href="<?= e(post_url($story)) ?>"><img src="<?= e(image_url($story['featured_image'])) ?>" alt="" loading="lazy"></a><div><a class="category-kicker" href="<?= e(url('category/' . rawurlencode($story['category_slug'] ?? ''))) ?>"><?= e($story['category_name'] ?? '') ?></a><h3><a href="<?= e(post_url($story)) ?>"><?= e($story['title']) ?></a></h3><time><?= e(format_date($story['published_at'])) ?></time></div></article>
        <?php endforeach; ?>
      </aside><?php endif; ?>
    </div>
  </section>
<?php else: ?>
  <section class="empty-home"><div class="container"><div class="empty-home-content"><span class="page-kicker">WISDOM NEWS</span><h1><?= e(setting('site_name', APP_NAME)) ?></h1><p>செய்திகளும் சிந்தனைகளும் விரைவில் இங்கே வெளியாகும்.</p><a class="outline-action" href="<?= e(url('search')) ?>">செய்திக் காப்பகத்தைத் தேடுக <span aria-hidden="true">→</span></a></div></div></section>
<?php endif; ?>

<?php if ($latest || $mostRead): ?>
  <section class="section-block container" id="latest-news">
    <div class="section-heading"><div><span class="section-label"><?= $latest ? '24/7 NEWSROOM' : 'POPULAR' ?></span><h2><?= $latest ? 'சமீபத்திய செய்திகள்' : 'வாசகர்கள் அதிகம் படித்தவை' ?></h2></div><a href="<?= e(url('search')) ?>">செய்திகளைத் தேடுக <span aria-hidden="true">→</span></a></div>
    <div class="latest-layout <?= !$latest ? 'latest-layout-ranking-only' : '' ?>">
      <?php if ($latest): ?><div class="latest-grid"><?php foreach ($latest as $post): ?><div><?php require ROOT_PATH . '/includes/article-card.php'; ?></div><?php endforeach; ?></div><?php endif; ?>
      <?php if ($mostRead): ?><aside class="ranking-panel" aria-labelledby="most-read-title"><div class="ranking-header"><span>POPULAR</span><h2 id="most-read-title">அதிகம் படிக்கப்பட்டவை</h2></div><ol><?php foreach ($mostRead as $popular): ?><li><a href="<?= e(post_url($popular)) ?>"><img class="ranking-thumb" src="<?= e(image_url($popular['featured_image'] ?? '')) ?>" alt="" loading="lazy"><span class="ranking-copy"><span class="ranking-category"><?= e($popular['category_name'] ?? '') ?></span><strong><?= e($popular['title']) ?></strong><small><?= number_format((int) $popular['views']) ?> பார்வைகள்</small></span></a></li><?php endforeach; ?></ol></aside><?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php foreach ($sections as $section): ?>
  <section class="section-block category-section <?= ($section['category']['slug'] ?? '') === 'wisdom-special' ? 'wisdom-special-section' : '' ?> container">
    <div class="section-heading"><div><span class="section-label">WISDOM NEWS</span><h2><?= e($section['category']['name']) ?></h2></div><a href="<?= e(category_url($section['category'])) ?>">மேலும் பார்க்க <span aria-hidden="true">→</span></a></div>
    <?php $featured = $section['posts'][0]; $morePosts = array_slice($section['posts'], 1); ?>
    <div class="category-editorial">
      <article class="category-feature"><a class="category-feature-media" href="<?= e(post_url($featured)) ?>"><img src="<?= e(image_url($featured['featured_image'] ?? '')) ?>" alt="<?= e($featured['image_alt'] ?: $featured['title']) ?>" loading="lazy"></a><div><span class="category-kicker"><?= e($featured['category_name'] ?? '') ?></span><h3><a href="<?= e(post_url($featured)) ?>"><?= e($featured['title']) ?></a></h3><p><?= e($featured['short_description'] ?: excerpt($featured['content'], 180)) ?></p><div class="story-meta"><time datetime="<?= e(date('c', strtotime($featured['published_at']))) ?>"><?= e(format_date($featured['published_at'])) ?></time><span><?= reading_time($featured['content']) ?> நிமிட வாசிப்பு</span></div><a class="inline-read-more" href="<?= e(post_url($featured)) ?>">முழுவதும் படிக்க <span aria-hidden="true">→</span></a></div></article>
      <?php if ($morePosts): ?><div class="category-story-list"><?php foreach ($morePosts as $story): ?><article class="story-row"><a class="story-row-media" href="<?= e(post_url($story)) ?>"><img src="<?= e(image_url($story['featured_image'] ?? '')) ?>" alt="" loading="lazy"></a><div><span class="category-kicker"><?= e($story['category_name'] ?? '') ?></span><h3><a href="<?= e(post_url($story)) ?>"><?= e($story['title']) ?></a></h3><time datetime="<?= e(date('c', strtotime($story['published_at']))) ?>"><?= e(format_date($story['published_at'])) ?></time></div></article><?php endforeach; ?></div><?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>

<?php if ($categories): ?>
  <section class="explore-section" aria-labelledby="explore-title">
    <div class="container">
      <div class="explore-intro"><span class="section-label">DISCOVER</span><h2 id="explore-title">உங்களுக்கான செய்திப் பிரிவுகள்</h2><p>உங்களுக்கு விருப்பமான தலைப்புகளைத் தேர்ந்தெடுத்து சமீபத்திய கட்டுரைகளை எளிதாகக் கண்டறியுங்கள்.</p></div>
      <div class="category-directory"><?php foreach ($categories as $directoryCategory): ?><a href="<?= e(category_url($directoryCategory)) ?>"><span><?= e($directoryCategory['name']) ?></span><small><?= number_format((int) ($categoryCountRows[$directoryCategory['id']] ?? 0)) ?> கட்டுரைகள்</small><strong aria-hidden="true">→</strong></a><?php endforeach; ?></div>
    </div>
  </section>
<?php endif; ?>
</main>
<?php $footerFlush = !empty($categories); require ROOT_PATH . '/includes/footer.php'; ?>
