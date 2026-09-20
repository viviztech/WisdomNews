<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_admin();

$keys = ['site_name', 'contact_email', 'facebook_url', 'youtube_url', 'instagram_url', 'footer_text'];
$errors = [];
if (is_post()) {
    verify_csrf();
    $values = [];
    foreach ($keys as $key) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    if ($values['site_name'] === '') $errors[] = 'Site name is required.';
    if ($values['contact_email'] !== '' && !filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Contact email is invalid.';
    foreach (['facebook_url', 'youtube_url', 'instagram_url'] as $urlKey) {
        if ($values[$urlKey] !== '' && !filter_var($values[$urlKey], FILTER_VALIDATE_URL)) $errors[] = ucfirst(str_replace('_url', '', $urlKey)) . ' URL is invalid.';
    }
    foreach (['site_logo', 'favicon'] as $imageKey) {
        if (!empty($_FILES[$imageKey]['name'])) {
            try {
                $values[$imageKey] = upload_image($_FILES[$imageKey], 'branding');
            } catch (RuntimeException $exception) {
                $errors[] = ucfirst(str_replace('_', ' ', $imageKey)) . ': ' . $exception->getMessage();
            }
        } else {
            $values[$imageKey] = setting($imageKey);
        }
    }
    if (!$errors) {
        $statement = db()->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        foreach ($values as $key => $value) {
            $statement->execute([$key, $value]);
        }
        flash('success', 'Settings updated.');
        redirect(url('admin/settings/'));
    }
}

$adminTitle = 'Settings';
$current = 'settings';
require ROOT_PATH . '/admin/includes/header.php';
?>
<header class="admin-page-head"><div><span class="page-eyebrow">CONFIGURATION</span><h1 class="page-title">Settings</h1><p class="page-description">Manage the public identity and contact details for Wisdom News.</p></div><a class="btn btn-outline-secondary" href="<?= e(url()) ?>" target="_blank" rel="noopener noreferrer">Preview website ↗</a></header>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?><section class="panel" style="max-width:850px"><div class="mb-3"><label class="form-label required" for="site_name">Site name</label><input class="form-control" id="site_name" name="site_name" value="<?= e($_POST['site_name'] ?? setting('site_name', APP_NAME)) ?>" required></div><div class="mb-3"><label class="form-label" for="contact_email">Contact email</label><input class="form-control" id="contact_email" type="email" name="contact_email" value="<?= e($_POST['contact_email'] ?? setting('contact_email')) ?>"></div><div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label" for="site_logo">Logo</label><?php if (setting('site_logo')): ?><img class="preview-image mb-2" src="<?= e(image_url(setting('site_logo'))) ?>" alt=""><?php endif; ?><input class="form-control" id="site_logo" type="file" name="site_logo" accept=".jpg,.jpeg,.png,.webp"></div><div class="col-md-6"><label class="form-label" for="favicon">Favicon</label><?php if (setting('favicon')): ?><img class="preview-image mb-2" src="<?= e(image_url(setting('favicon'))) ?>" alt=""><?php endif; ?><input class="form-control" id="favicon" type="file" name="favicon" accept=".jpg,.jpeg,.png,.webp"></div></div><?php foreach (['facebook_url' => 'Facebook URL', 'youtube_url' => 'YouTube URL', 'instagram_url' => 'Instagram URL'] as $key => $label): ?><div class="mb-3"><label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label><input class="form-control" id="<?= e($key) ?>" type="url" name="<?= e($key) ?>" value="<?= e($_POST[$key] ?? setting($key)) ?>"></div><?php endforeach; ?><div class="mb-4"><label class="form-label" for="footer_text">Footer text</label><textarea class="form-control" id="footer_text" name="footer_text" rows="3"><?= e($_POST['footer_text'] ?? setting('footer_text')) ?></textarea></div><button class="btn btn-success" type="submit">Save Settings</button></section></form>
<?php require ROOT_PATH . '/admin/includes/footer.php'; ?>
