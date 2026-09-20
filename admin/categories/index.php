<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_admin();

$editId = max(0, (int) ($_GET['edit'] ?? 0));
$editing = ['id' => 0, 'name' => '', 'slug' => '', 'description' => '', 'status' => 'active', 'sort_order' => 0, 'parent_id' => ''];
if ($editId) {
    $statement = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $statement->execute([$editId]);
    $editing = $statement->fetch() ?: $editing;
}
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete') {
        $used = db()->prepare('SELECT (SELECT COUNT(*) FROM posts WHERE category_id=?) + (SELECT COUNT(*) FROM categories WHERE parent_id=?)');
        $used->execute([$id, $id]);
        if ((int) $used->fetchColumn() > 0) {
            flash('danger', 'This category contains posts or child categories and cannot be deleted.');
        } else {
            $statement = db()->prepare('DELETE FROM categories WHERE id = ?');
            $statement->execute([$id]);
            flash('success', 'Category deleted.');
        }
        redirect(url('admin/categories/'));
    }
    $editing = [
        'id' => $id,
        'name' => trim((string) ($_POST['name'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'status' => ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active',
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'parent_id' => (int) ($_POST['parent_id'] ?? 0) ?: null,
    ];
    if ($editing['name'] === '') $errors[] = 'Name is required.';
    if ($editing['parent_id'] === $id && $id) $errors[] = 'A category cannot be its own parent.';
    $editing['slug'] = unique_slug('categories', $editing['slug'] ?: $editing['name'], $id ?: null);
    if (!$errors) {
        if ($id) {
            $statement = db()->prepare('UPDATE categories SET parent_id=?,name=?,slug=?,description=?,status=?,sort_order=?,updated_at=NOW() WHERE id=?');
            $statement->execute([$editing['parent_id'], $editing['name'], $editing['slug'], $editing['description'], $editing['status'], $editing['sort_order'], $id]);
        } else {
            $statement = db()->prepare('INSERT INTO categories (parent_id,name,slug,description,status,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
            $statement->execute([$editing['parent_id'], $editing['name'], $editing['slug'], $editing['description'], $editing['status'], $editing['sort_order']]);
        }
        flash('success', 'Category saved.');
        redirect(url('admin/categories/'));
    }
}

$categories = db()->query('SELECT c.*,p.name parent_name,(SELECT COUNT(*) FROM posts WHERE category_id=c.id) post_count FROM categories c LEFT JOIN categories p ON p.id=c.parent_id ORDER BY c.sort_order,c.name')->fetchAll();
$adminTitle = 'Categories';
$current = 'categories';
require ROOT_PATH . '/admin/includes/header.php';
?>
<header class="admin-page-head"><div><span class="page-eyebrow">TAXONOMY</span><h1 class="page-title">Categories</h1><p class="page-description">Organize public navigation and editorial sections.</p></div></header>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-4"><section class="panel"><h2 class="h5 mb-3"><?= $editing['id'] ? 'Edit Category' : 'Add Category' ?></h2><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><div class="mb-3"><label class="form-label required" for="name">Name</label><input class="form-control" id="name" name="name" value="<?= e($editing['name']) ?>" data-slug-title required></div><div class="mb-3"><label class="form-label required" for="slug">Slug</label><input class="form-control" id="slug" name="slug" value="<?= e($editing['slug']) ?>" data-slug-input required></div><div class="mb-3"><label class="form-label" for="parent_id">Parent category</label><select class="form-select" id="parent_id" name="parent_id"><option value="">None</option><?php foreach ($categories as $category): if ((int) $category['id'] === (int) $editing['id']) continue; ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($editing['parent_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4"><?= e($editing['description']) ?></textarea></div><div class="row g-3 mb-3"><div class="col-7"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="active" <?= $editing['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $editing['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div><div class="col-5"><label class="form-label" for="sort_order">Order</label><input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= (int) $editing['sort_order'] ?>"></div></div><button class="btn btn-success" type="submit">Save Category</button><?php if ($editing['id']): ?><a class="btn btn-outline-secondary" href="<?= e(url('admin/categories/')) ?>">Cancel</a><?php endif; ?></form></section></div><div class="col-lg-8"><section class="panel"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Name</th><th>Parent</th><th>Posts</th><th>Status</th><th>Order</th><th></th></tr></thead><tbody><?php foreach ($categories as $category): ?><tr><td><strong><?= e($category['name']) ?></strong><div class="small text-secondary"><?= e($category['slug']) ?></div></td><td><?= e($category['parent_name'] ?? '—') ?></td><td><?= (int) $category['post_count'] ?></td><td><span class="status status-<?= e($category['status']) ?>"><?= e(ucfirst($category['status'])) ?></span></td><td><?= (int) $category['sort_order'] ?></td><td><div class="d-flex gap-2"><a href="<?= e(url('admin/categories/?edit=' . $category['id'])) ?>">Edit</a><form method="post" data-confirm="Delete this category permanently?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="btn btn-link text-danger p-0">Delete</button></form></div></td></tr><?php endforeach; ?><?php if (!$categories): ?><tr><td colspan="6" class="text-center text-secondary py-5">No categories yet.</td></tr><?php endif; ?></tbody></table></div></section></div></div>
<?php require ROOT_PATH . '/admin/includes/footer.php'; ?>
