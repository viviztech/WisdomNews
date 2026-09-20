<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
http_response_code(404);
$pageTitle = 'பக்கம் கிடைக்கவில்லை';
$metaDescription = 'நீங்கள் தேடிய பக்கம் கிடைக்கவில்லை.';
$latest = db()->query("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='published' AND p.published_at <= NOW() ORDER BY p.published_at DESC LIMIT 3")->fetchAll();
require ROOT_PATH . '/includes/header.php';
?>
<main class="container py-5">
  <section class="error-page text-center mx-auto" aria-labelledby="error-title">
    <div class="error-code">404</div>
    <span class="section-label">PAGE NOT FOUND</span>
    <h1 id="error-title">இந்தப் பக்கம் கிடைக்கவில்லை</h1>
    <p class="text-secondary">முகவரி மாறியிருக்கலாம் அல்லது பக்கம் நீக்கப்பட்டிருக்கலாம்.</p>
    <a class="outline-action mt-2" href="<?= e(url()) ?>">முகப்புக்குச் செல்லுங்கள் <span aria-hidden="true">→</span></a>
  </section>
  <?php if ($latest): ?>
    <section class="section-block mt-5">
      <div class="section-heading"><h2>சமீபத்திய செய்திகள்</h2></div>
      <div class="row g-4"><?php foreach ($latest as $post): ?><div class="col-md-4"><?php require ROOT_PATH . '/includes/article-card.php'; ?></div><?php endforeach; ?></div>
    </section>
  <?php endif; ?>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
