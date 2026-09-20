<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_admin();

if (!is_post()) {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$statement = db()->prepare('DELETE FROM posts WHERE id = ?');
$statement->execute([$id]);
flash('success', $statement->rowCount() ? 'Post deleted.' : 'Post not found.');
redirect(url('admin/posts/'));

