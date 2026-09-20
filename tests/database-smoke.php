<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/config.php';

$pdo = db();
$suffix = bin2hex(random_bytes(5));
$pdo->beginTransaction();

try {
    $category = $pdo->prepare("INSERT INTO categories (name,slug,description,status,sort_order,created_at,updated_at) VALUES (?,?,?,'active',0,NOW(),NOW())");
    $category->execute(['Smoke Test', 'smoke-' . $suffix, 'Temporary CRUD check']);
    $categoryId = (int) $pdo->lastInsertId();

    $post = $pdo->prepare("INSERT INTO posts (category_id,title,slug,short_description,content,status,published_at,created_at,updated_at) VALUES (?,?,?,?,?,'draft',NOW(),NOW(),NOW())");
    $post->execute([$categoryId, 'Smoke test post', 'smoke-post-' . $suffix, 'Temporary', '<p>தமிழ் சோதனை</p>']);
    $postId = (int) $pdo->lastInsertId();

    $update = $pdo->prepare("UPDATE posts SET title=?,status='published',updated_at=NOW() WHERE id=?");
    $update->execute(['Smoke test post updated', $postId]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Post update failed.');
    }

    $search = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE id=? AND title LIKE ? AND content LIKE ?");
    $search->execute([$postId, '%updated%', '%தமிழ்%']);
    if ((int) $search->fetchColumn() !== 1) {
        throw new RuntimeException('Post search/read failed.');
    }

    $pdo->prepare('DELETE FROM posts WHERE id=?')->execute([$postId]);
    $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$categoryId]);
    $pdo->rollBack();
    fwrite(STDOUT, "Database create/read/update/delete checks passed.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

