<article class="news-card h-100">
  <a class="card-image" href="<?= e(post_url($post)) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e(image_url($post['featured_image'] ?? '')) ?>" alt="<?= e($post['image_alt'] ?: $post['title']) ?>" loading="lazy"></a>
  <div class="card-body">
    <?php if (!empty($post['category_name'])): ?><a class="category-kicker" href="<?= e(url('category/' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a><?php endif; ?>
    <h3><a href="<?= e(post_url($post)) ?>"><?= e($post['title']) ?></a></h3>
    <p><?= e($post['short_description'] ?: excerpt($post['content'])) ?></p>
    <div class="card-meta"><div><time datetime="<?= e(date('c', strtotime($post['published_at']))) ?>"><?= e(format_date($post['published_at'])) ?></time><span><?= reading_time($post['content']) ?> நிமிட வாசிப்பு</span></div><a class="card-read-more" href="<?= e(post_url($post)) ?>" aria-label="<?= e($post['title']) ?> — முழுவதும் படிக்க">படிக்க <span aria-hidden="true">→</span></a></div>
  </div>
</article>
