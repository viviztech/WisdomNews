<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_admin();

$id = max(0, (int) ($_GET['id'] ?? 0));
$post = ['title' => '', 'slug' => '', 'category_id' => '', 'short_description' => '', 'content' => '', 'featured_image' => '', 'image_alt' => '', 'status' => 'draft', 'published_at' => date('Y-m-d H:i:s')];
if ($id) {
    $statement = db()->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
    $statement->execute([$id]);
    $post = $statement->fetch() ?: [];
    if (!$post) {
        flash('danger', 'Post not found.');
        redirect(url('admin/posts/'));
    }
}
$categories = db()->query("SELECT id,name,status FROM categories ORDER BY sort_order,name")->fetchAll();
$errors = [];

if (is_post()) {
    verify_csrf();
    $post = array_merge($post, [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'short_description' => trim((string) ($_POST['short_description'] ?? '')),
        'content' => sanitize_html((string) ($_POST['content'] ?? '')),
        'image_alt' => trim((string) ($_POST['image_alt'] ?? '')),
        'status' => ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft',
        'published_at' => trim((string) ($_POST['published_at'] ?? '')),
    ]);
    if ($post['title'] === '') $errors[] = 'Title is required.';
    if (!$post['category_id']) $errors[] = 'Category is required.';
    if (trim(strip_tags($post['content'])) === '' && !str_contains($post['content'], '<img')) $errors[] = 'Content is required.';
    if ($post['status'] === 'published' && $post['published_at'] === '') $errors[] = 'Published date is required for published posts.';
    $post['slug'] = unique_slug('posts', $post['slug'] ?: $post['title'], $id ?: null);
    if (!empty($_FILES['featured_image']['name'])) {
        try {
            $post['featured_image'] = upload_image($_FILES['featured_image']);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
    if (!$errors) {
        $timestamp = $post['published_at'] !== '' ? strtotime($post['published_at']) : false;
        $publishedAt = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
        if ($post['status'] === 'published' && !$publishedAt) {
            $errors[] = 'Published date is invalid.';
        } elseif ($id) {
            $statement = db()->prepare('UPDATE posts SET category_id=?,title=?,slug=?,short_description=?,content=?,featured_image=?,image_alt=?,status=?,published_at=?,updated_at=NOW() WHERE id=?');
            $statement->execute([$post['category_id'], $post['title'], $post['slug'], $post['short_description'], $post['content'], $post['featured_image'] ?: null, $post['image_alt'], $post['status'], $publishedAt, $id]);
        } else {
            $statement = db()->prepare('INSERT INTO posts (category_id,title,slug,short_description,content,featured_image,image_alt,status,published_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
            $statement->execute([$post['category_id'], $post['title'], $post['slug'], $post['short_description'], $post['content'], $post['featured_image'] ?: null, $post['image_alt'], $post['status'], $publishedAt]);
            $id = (int) db()->lastInsertId();
        }
        if (!$errors) {
            flash('success', 'Post saved successfully.');
            redirect(url('admin/posts/edit.php?id=' . $id));
        }
    }
}

$adminTitle = $id ? 'Edit Post' : 'Add Post';
$current = 'posts';
require ROOT_PATH . '/admin/includes/header.php';
$publishedInput = $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '';
?>
<header class="admin-page-head"><div><span class="page-eyebrow">EDITOR</span><h1 class="page-title"><?= $id ? 'Edit Post' : 'Add New Post' ?></h1><p class="page-description">Write, format, and publish in Tamil or English.</p></div><a class="btn btn-outline-secondary" href="<?= e(url('admin/posts/')) ?>">← Back to posts</a></header>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if (!$categories): ?><div class="alert alert-warning">No categories are available. <a class="alert-link" href="<?= e(url('admin/categories/')) ?>">Create a category first</a>, then return to this post.</div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?><div class="row g-4"><div class="col-xl-8"><section class="panel mb-4"><div class="mb-3"><label class="form-label required" for="title">Title</label><input class="form-control form-control-lg" id="title" name="title" value="<?= e($post['title']) ?>" data-slug-title required></div><div class="mb-3"><label class="form-label required" for="slug">Slug</label><input class="form-control" id="slug" name="slug" value="<?= e($post['slug']) ?>" data-slug-input required><div class="form-text">Used in the public article URL. Existing migrated slugs should not be changed.</div></div><div class="mb-3"><label class="form-label" for="short_description">Short description</label><textarea class="form-control" id="short_description" name="short_description" rows="3" maxlength="1000"><?= e($post['short_description']) ?></textarea></div><label class="form-label required">Content</label><div class="editor-toolbar" role="toolbar" aria-label="Content formatting"><button type="button" data-command="formatBlock" data-value="p">P</button><button type="button" data-command="formatBlock" data-value="h2">H2</button><button type="button" data-command="bold"><strong>B</strong></button><button type="button" data-command="italic"><em>I</em></button><button type="button" data-command="insertUnorderedList">• List</button><button type="button" data-command="insertOrderedList">1. List</button><button type="button" data-command="createLink" onclick="this.dataset.value=prompt('Link URL')||''">Link</button></div><div class="rich-editor" contenteditable="true" data-editor><?= sanitize_html((string) $post['content']) ?></div><textarea class="d-none" name="content" data-editor-source><?= e($post['content']) ?></textarea></section></div><div class="col-xl-4"><section class="panel mb-4"><div class="mb-3"><label class="form-label required" for="category_id">Category</label><select class="form-select" id="category_id" name="category_id" required><option value="">Choose category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $post['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?><?= $category['status'] === 'inactive' ? ' (inactive)' : '' ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></div><div class="mb-3"><label class="form-label" for="published_at">Published date</label><input class="form-control" id="published_at" type="datetime-local" name="published_at" value="<?= e($publishedInput) ?>"></div><button class="btn btn-success w-100" type="submit">Save Post</button><?php if ($id && $post['status'] === 'published'): ?><a class="btn btn-outline-secondary w-100 mt-2" href="<?= e(post_url($post)) ?>" target="_blank">View Post</a><?php endif; ?></section><section class="panel"><label class="form-label" for="featured_image">Featured image</label><?php if ($post['featured_image']): ?><img class="preview-image mb-3" src="<?= e(image_url($post['featured_image'])) ?>" alt=""><?php endif; ?><input class="form-control" id="featured_image" type="file" name="featured_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG or WEBP. Maximum <?= (int) (MAX_UPLOAD_BYTES / 1024 / 1024) ?> MB.</div><div class="mt-3"><label class="form-label" for="image_alt">Image alt text</label><input class="form-control" id="image_alt" name="image_alt" value="<?= e($post['image_alt']) ?>"></div></section></div></div></form>
<?php require ROOT_PATH . '/admin/includes/footer.php'; ?>
