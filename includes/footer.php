<?php
$footerCategories = array_slice(active_categories(), 0, 8);
$footerSocials = [];
foreach ([
    'facebook_url' => ['Facebook', 'f'],
    'youtube_url' => ['YouTube', '▶'],
    'instagram_url' => ['Instagram', '◎'],
] as $key => [$label, $icon]) {
    if (setting($key)) {
        $footerSocials[] = ['label' => $label, 'icon' => $icon, 'url' => setting($key)];
    }
}
?>
<footer class="site-footer footer-v2 mt-5 <?= !empty($footerFlush) ? 'footer-flush' : '' ?>">
  <span class="footer-shape footer-shape-one" aria-hidden="true"></span>
  <span class="footer-shape footer-shape-two" aria-hidden="true"></span>
  <div class="container footer-main">
    <div class="footer-grid">
      <section class="footer-column footer-about" aria-labelledby="footer-brand-title">
        <a class="footer-brand" href="<?= e(url()) ?>" id="footer-brand-title" aria-label="<?= e(setting('site_name', APP_NAME)) ?> முகப்பு">
          <?php if (setting('site_logo')): ?>
            <img src="<?= e(image_url(setting('site_logo'))) ?>" alt="<?= e(setting('site_name', APP_NAME)) ?>">
          <?php else: ?>
            <span class="footer-wordmark"><strong>WISDOM</strong><small>NEWS</small></span>
          <?php endif; ?>
        </a>
        <p class="footer-copy"><?= e(setting('footer_text', 'உண்மையை அறிந்து, அறிவுடன் முன்னேறுவோம்.')) ?></p>
        <p class="footer-note">செய்திகள், சிந்தனைகள் மற்றும் ஆழமான பார்வைகளை தெளிவாக வழங்கும் தமிழ் செய்தித் தளம்.</p>
        <?php if ($footerSocials): ?>
          <nav class="footer-socials" aria-label="சமூக வலைத்தளங்கள்">
            <?php foreach ($footerSocials as $social): ?><a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($social['label']) ?>"><span aria-hidden="true"><?= e($social['icon']) ?></span></a><?php endforeach; ?>
          </nav>
        <?php endif; ?>
      </section>

      <nav class="footer-column footer-categories" aria-labelledby="footer-categories-title">
        <h2 class="footer-title" id="footer-categories-title">பிரிவுகள்</h2>
        <ul class="footer-links"><?php foreach ($footerCategories as $category): ?><li><a href="<?= e(category_url($category)) ?>"><span><?= e($category['name']) ?></span><span aria-hidden="true">→</span></a></li><?php endforeach; ?></ul>
      </nav>

      <section class="footer-column footer-connect" aria-labelledby="footer-connect-title">
        <h2 class="footer-title" id="footer-connect-title">தொடர்பில் இருங்கள்</h2>
        <p>Wisdom News செய்திகளையும் கட்டுரைகளையும் விரைவாகத் தேடுங்கள்.</p>
        <form class="footer-search" action="<?= e(url('search')) ?>" method="get" role="search">
          <label class="visually-hidden" for="footer-search-q">செய்திகளைத் தேடுங்கள்</label>
          <input id="footer-search-q" name="q" type="search" placeholder="செய்திகளைத் தேடுங்கள்…" required>
          <button type="submit" aria-label="தேடு"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35m2.35-5.4A7.75 7.75 0 1 1 3.5 11.25a7.75 7.75 0 0 1 15.5 0Z"/></svg></button>
        </form>
        <?php if (setting('contact_email')): ?><a class="footer-email" href="mailto:<?= e(setting('contact_email')) ?>"><span aria-hidden="true">✉</span><?= e(setting('contact_email')) ?></a><?php endif; ?>
      </section>
    </div>
  </div>
  <div class="footer-bottom"><div class="container"><span>© <?= date('Y') ?> <?= e(setting('site_name', APP_NAME)) ?>. அனைத்து உரிமைகளும் பாதுகாக்கப்பட்டவை.</span><a href="#" class="back-to-top" aria-label="பக்கத்தின் மேலே செல்ல">மேலே செல்ல <span aria-hidden="true">↑</span></a></div></div>
</footer>
<button class="floating-top" type="button" data-floating-top aria-label="பக்கத்தின் மேலே செல்ல"><span aria-hidden="true">↑</span></button>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(versioned_asset('js/site.js')) ?>"></script>
</body>
</html>
