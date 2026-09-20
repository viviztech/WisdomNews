<?php
$siteName = setting('site_name', APP_NAME);
$pageTitle = $pageTitle ?? $siteName;
$metaDescription = $metaDescription ?? setting('footer_text', 'Wisdom News — Tamil and English news, ideas and analysis.');
$canonical = $canonical ?? url(ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/'));
$ogImage = $ogImage ?? image_url(setting('site_logo'));
$categoryTree = active_categories(true);
$favicon = setting('favicon');
$requestPath = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/');
$appPath = trim((string) (parse_url(APP_URL, PHP_URL_PATH) ?: ''), '/');
if ($appPath !== '' && ($requestPath === $appPath || str_starts_with($requestPath, $appPath . '/'))) {
    $requestPath = trim(substr($requestPath, strlen($appPath)), '/');
}
$isHomePage = $requestPath === '';
$activeCategorySlug = (string) ($category['slug'] ?? $post['category_slug'] ?? '');
$displayDate = format_date(date('Y-m-d H:i:s'), 'l, d F Y');
?>
<!doctype html>
<html lang="ta" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle === $siteName ? $siteName : $pageTitle . ' | ' . $siteName) ?></title>
  <meta name="description" content="<?= e(excerpt($metaDescription, 160)) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:type" content="<?= isset($isArticle) ? 'article' : 'website' ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e(excerpt($metaDescription, 180)) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <?php if (isset($isArticle, $post)): ?>
  <meta property="article:published_time" content="<?= e(date('c', strtotime($post['published_at']))) ?>">
  <meta property="article:modified_time" content="<?= e(date('c', strtotime($post['updated_at']))) ?>">
  <?php endif; ?>
  <?php if ($favicon): ?><link rel="icon" href="<?= e(image_url($favicon)) ?>"><?php endif; ?>
  <?php if (isset($isArticle, $post)): $articleSchema = [
      '@context' => 'https://schema.org',
      '@type' => 'NewsArticle',
      'headline' => $post['title'],
      'description' => $metaDescription,
      'image' => [$ogImage],
      'datePublished' => date('c', strtotime($post['published_at'])),
      'dateModified' => date('c', strtotime($post['updated_at'])),
      'articleSection' => $post['category_name'] ?? '',
      'mainEntityOfPage' => $canonical,
      'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'logo' => ['@type' => 'ImageObject', 'url' => image_url(setting('site_logo'))]],
  ]; ?>
  <script type="application/ld+json"><?= json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Madurai:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,600;6..72,700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(versioned_asset('css/site.css')) ?>" rel="stylesheet">
  <link href="<?= e(versioned_asset('css/professional.css')) ?>" rel="stylesheet">
</head>
<body data-image-fallback="<?= e(asset('images/news-placeholder.svg')) ?>">
<a class="visually-hidden-focusable" href="#main-content">Skip to content</a>
<header class="site-header" data-site-header>
  <div class="utility-bar">
    <div class="container utility-inner">
      <div class="utility-date"><span class="live-indicator" aria-hidden="true"></span><time datetime="<?= e(date('Y-m-d')) ?>"><?= e($displayDate) ?></time></div>
      <div class="utility-links"><span class="language-current" lang="ta">தமிழ்</span><span aria-hidden="true">•</span><a href="<?= e(url('#latest-news')) ?>">சமீபத்திய செய்திகள்</a><span aria-hidden="true">•</span><a href="<?= e(url('search')) ?>">தேடல்</a></div>
    </div>
  </div>
  <div class="masthead container">
    <div class="brand-cluster">
      <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($siteName) ?> முகப்பு">
        <?php if (setting('site_logo')): ?>
          <img src="<?= e(image_url(setting('site_logo'))) ?>" alt="<?= e($siteName) ?>">
        <?php else: ?>
          <span class="brand-mark">W</span><span><strong><?= e($siteName) ?></strong><small>அறிவின் வழி • உண்மையின் மொழி</small></span>
        <?php endif; ?>
      </a>
      <span class="brand-edition">தமிழ் டிஜிட்டல் செய்தித் தளம்</span>
    </div>
    <div class="header-actions">
      <a class="trending-shortcut" href="<?= e(url('#latest-news')) ?>" aria-label="தற்போதைய முக்கிய செய்திகளுக்குச் செல்ல"><span aria-hidden="true"></span><b>டிரெண்டிங்</b></a>
      <form class="header-search d-none d-md-flex" action="<?= e(url('search')) ?>" method="get" role="search">
        <label class="visually-hidden" for="header-q">செய்திகளைத் தேடுங்கள்</label>
        <input id="header-q" name="q" type="search" placeholder="செய்திகளைத் தேடுங்கள்…" value="<?= e($_GET['q'] ?? '') ?>">
        <button type="submit" aria-label="தேடு"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m21 21-4.35-4.35m2.35-5.4A7.75 7.75 0 1 1 3.5 11.25a7.75 7.75 0 0 1 15.5 0Z"/></svg></button>
      </form>
    </div>
  </div>
  <nav class="navbar navbar-expand-lg news-nav" aria-label="முக்கிய வழிசெலுத்தல்">
    <div class="container">
      <a class="nav-home <?= $isHomePage ? 'is-active' : '' ?>" href="<?= e(url()) ?>" <?= $isHomePage ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5V21h-6v-6H9v6H3Z"/></svg><span class="visually-hidden">முகப்பு</span></a>
      <span class="mobile-nav-label d-lg-none">செய்திப் பிரிவுகள்</span>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="வழிசெலுத்தலை திறக்க"><span></span><span></span><span></span></button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto">
          <?php foreach ($categoryTree[0] ?? [] as $navCategory): $children = $categoryTree[(int) $navCategory['id']] ?? []; $navIsActive = $activeCategorySlug === $navCategory['slug'] || in_array($activeCategorySlug, array_column($children, 'slug'), true); ?>
            <li class="nav-item <?= $children ? 'dropdown has-children' : '' ?>">
              <a class="nav-link <?= $navIsActive ? 'active' : '' ?>" href="<?= e(category_url($navCategory)) ?>" <?= $activeCategorySlug === $navCategory['slug'] ? 'aria-current="page"' : '' ?>><?= e($navCategory['name']) ?></a>
              <?php if ($children): ?><button class="nav-submenu-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= e($navCategory['name']) ?> துணைப் பிரிவுகளைத் திறக்க"></button><?php endif; ?>
              <?php if ($children): ?><ul class="dropdown-menu"><?php foreach ($children as $navChild): ?><li><a class="dropdown-item <?= $activeCategorySlug === $navChild['slug'] ? 'active' : '' ?>" href="<?= e(category_url($navChild)) ?>" <?= $activeCategorySlug === $navChild['slug'] ? 'aria-current="page"' : '' ?>><?= e($navChild['name']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <form class="mobile-search d-flex d-md-none mt-3 mb-2" action="<?= e(url('search')) ?>" method="get"><input class="form-control" name="q" type="search" placeholder="தேடுங்கள்…"><button class="btn btn-light ms-2">தேடு</button></form>
      </div>
    </div>
  </nav>
  <?php if (isset($isArticle)): ?><div class="reading-progress" aria-hidden="true"><span data-reading-progress></span></div><?php endif; ?>
</header>
