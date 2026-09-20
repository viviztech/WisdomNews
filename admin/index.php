<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_admin();

$counts = db()->query("SELECT COUNT(*) total, SUM(status='published') published, SUM(status='draft') drafts FROM posts")->fetch();
$categoryCount = (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$recent = db()->query("SELECT p.id,p.title,p.status,p.published_at,c.name category_name FROM posts p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC LIMIT 8")->fetchAll();
$adminTitle = 'Dashboard';
$current = 'dashboard';
require ROOT_PATH . '/admin/includes/header.php';
?>
<header class="admin-page-head"><div><span class="page-eyebrow">OVERVIEW</span><h1 class="page-title">Dashboard</h1><p class="page-description">A clear snapshot of your newsroom and publishing activity.</p></div><a class="btn btn-success" href="<?= e(url('admin/posts/edit.php')) ?>">+ Add New Post</a></header>
<div class="row g-3 stat-grid"><?php foreach ([['Total posts',$counts['total'] ?? 0,'posts','▤'],['Published',$counts['published'] ?? 0,'published','✓'],['Drafts',$counts['drafts'] ?? 0,'drafts','✎'],['Categories',$categoryCount,'categories','◇']] as [$label,$value,$tone,$icon]): ?><div class="col-6 col-xl-3"><div class="stat-card <?= e($tone) ?>"><div class="stat-card-head"><span class="stat-card-label"><?= e($label) ?></span><span class="stat-icon" aria-hidden="true"><?= e($icon) ?></span></div><div class="stat-number"><?= number_format((int) $value) ?></div></div></div><?php endforeach; ?></div>
<section class="panel"><div class="panel-heading"><div><h2>Recent posts</h2><p>Your latest editorial activity.</p></div><a href="<?= e(url('admin/posts/')) ?>">View all posts →</a></div><div class="table-responsive"><table class="table"><thead><tr><th scope="col">Title</th><th scope="col">Category</th><th scope="col">Status</th><th scope="col">Date</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead><tbody><?php foreach ($recent as $post): ?><tr><td><strong><?= e($post['title']) ?></strong></td><td><?= e($post['category_name'] ?? '—') ?></td><td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(format_date($post['published_at'])) ?></td><td><a href="<?= e(url('admin/posts/edit.php?id=' . $post['id'])) ?>">Edit</a></td></tr><?php endforeach; ?><?php if (!$recent): ?><tr><td colspan="5" class="empty-admin">No posts yet. Create your first story to begin publishing.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require ROOT_PATH . '/admin/includes/footer.php'; ?>
