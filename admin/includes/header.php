<?php
require_admin();
$adminTitle = $adminTitle ?? 'Dashboard';
$current = $current ?? 'dashboard';
$adminName = (string) (admin_user()['name'] ?? 'Administrator');
$adminInitial = function_exists('mb_substr') ? mb_substr($adminName, 0, 1, 'UTF-8') : substr($adminName, 0, 1);
$adminCssVersion = (string) (@filemtime(ROOT_PATH . '/admin/assets/admin.css') ?: 1);
?>
<!doctype html>
<html lang="en" data-admin-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($adminTitle) ?> | Wisdom News Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(url('admin/assets/admin.css?v=' . $adminCssVersion)) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
    <div class="sidebar-head">
      <a class="admin-brand" href="<?= e(url('admin/')) ?>">
        <?php if (setting('site_logo')): ?>
          <img class="admin-brand-logo" src="<?= e(image_url(setting('site_logo'))) ?>" alt="<?= e(setting('site_name', APP_NAME)) ?>">
        <?php else: ?>
          <span class="admin-brand-mark">W</span><span><strong>Wisdom News</strong><small>NEWSROOM</small></span>
        <?php endif; ?>
      </a>
      <button class="sidebar-close d-lg-none" type="button" data-sidebar-close aria-label="Close navigation">×</button>
    </div>
    <span class="nav-section-label">Workspace</span>
    <nav class="admin-nav">
      <a class="<?= $current === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('admin/')) ?>" <?= $current === 'dashboard' ? 'aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true">⌂</span><span>Dashboard</span></a>
      <a class="<?= $current === 'posts' ? 'active' : '' ?>" href="<?= e(url('admin/posts/')) ?>" <?= $current === 'posts' ? 'aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true">▤</span><span>Posts</span></a>
      <a class="<?= $current === 'categories' ? 'active' : '' ?>" href="<?= e(url('admin/categories/')) ?>" <?= $current === 'categories' ? 'aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true">◇</span><span>Categories</span></a>
      <a class="<?= $current === 'settings' ? 'active' : '' ?>" href="<?= e(url('admin/settings/')) ?>" <?= $current === 'settings' ? 'aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true">⚙</span><span>Settings</span></a>
    </nav>
    <div class="sidebar-footer">
      <a class="site-preview-link" href="<?= e(url()) ?>" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">↗</span><span>View public site</span></a>
      <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button class="admin-nav-button" type="submit"><span aria-hidden="true">⇥</span><span>Sign out</span></button></form>
    </div>
  </aside>
  <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation" tabindex="-1"></button>
  <div class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-start">
        <button class="sidebar-toggle d-lg-none" type="button" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open navigation"><span></span><span></span><span></span></button>
        <div><span class="topbar-kicker">WISDOM NEWSROOM</span><strong><?= e($adminTitle) ?></strong></div>
      </div>
      <div class="topbar-actions">
        <a class="view-site-button" href="<?= e(url()) ?>" target="_blank" rel="noopener noreferrer">View site <span aria-hidden="true">↗</span></a>
        <div class="admin-profile"><span class="admin-avatar" aria-hidden="true"><?= e(strtoupper($adminInitial)) ?></span><span><small>Signed in as</small><strong><?= e($adminName) ?></strong></span></div>
      </div>
    </header>
    <main class="admin-content" id="admin-content">
      <?php foreach (pull_flashes() as $message): ?><div class="alert alert-<?= e($message['type']) ?> alert-dismissible fade show admin-alert" role="alert"><?= e($message['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endforeach; ?>
