<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_admin();

$query = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$where = '';
$params = [];
if ($query !== '') {
    $where = ' WHERE p.title LIKE :query';
    $params['query'] = '%' . $query . '%';
}
$count = db()->prepare('SELECT COUNT(*) FROM posts p' . $where);
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$statement = db()->prepare('SELECT p.*,c.name category_name FROM posts p LEFT JOIN categories c ON c.id=p.category_id' . $where . ' ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset');
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$posts = $statement->fetchAll();

$adminTitle = 'Posts';
$current = 'posts';
require ROOT_PATH . '/admin/includes/header.php';
?>
<header class="admin-page-head"><div><span class="page-eyebrow">CONTENT</span><h1 class="page-title">Posts</h1><p class="page-description"><?= number_format($total) ?> posts in your newsroom.</p></div><a class="btn btn-success" href="<?= e(url('admin/posts/edit.php')) ?>">+ Add New Post</a></header>
<section class="panel"><div class="panel-heading"><div><h2>All posts</h2><p>Search, edit, preview, or remove newsroom content.</p></div></div><form class="row g-2 mb-4 admin-search" method="get"><div class="col-sm-8 col-lg-5"><label class="visually-hidden" for="post-search">Search posts</label><input class="form-control" id="post-search" name="q" type="search" value="<?= e($query) ?>" placeholder="Search post titles…"></div><div class="col-auto"><button class="btn btn-outline-secondary">Search</button></div><?php if ($query !== ''): ?><div class="col-auto"><a class="btn btn-link" href="<?= e(url('admin/posts/')) ?>">Clear</a></div><?php endif; ?></form><div class="table-responsive"><table class="table"><thead><tr><th scope="col">Title</th><th scope="col">Category</th><th scope="col">Status</th><th scope="col">Published date</th><th scope="col">Actions</th></tr></thead><tbody><?php foreach ($posts as $post): ?><tr><td><strong><?= e($post['title']) ?></strong></td><td><?= e($post['category_name'] ?? '—') ?></td><td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(format_date($post['published_at'])) ?></td><td><div class="table-actions"><a href="<?= e(url('admin/posts/edit.php?id=' . $post['id'])) ?>">Edit</a><?php if ($post['status'] === 'published'): ?><a href="<?= e(post_url($post)) ?>" target="_blank" rel="noopener noreferrer">View</a><?php endif; ?><form method="post" action="<?= e(url('admin/posts/delete.php')) ?>" data-confirm="Delete this post permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><button class="btn btn-link text-danger p-0" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?><?php if (!$posts): ?><tr><td colspan="5" class="empty-admin">No matching posts found.</td></tr><?php endif; ?></tbody></table></div><?= pagination($page, $pages, url('admin/posts/' . ($query ? '?q=' . rawurlencode($query) : ''))) ?></section>
<?php require ROOT_PATH . '/admin/includes/footer.php'; ?>
