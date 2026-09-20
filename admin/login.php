<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (admin_user()) {
    redirect(url('admin/'));
}

$error = '';
if (is_post()) {
    verify_csrf();
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    $lastAttempt = (int) ($_SESSION['last_login_attempt'] ?? 0);
    if ($attempts >= 5 && time() - $lastAttempt < 300) {
        $error = 'Too many login attempts. Please wait five minutes and try again.';
    } else {
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $statement = db()->prepare('SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1');
        $statement->execute([$identity, $identity]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = ['id' => (int) $admin['id'], 'name' => $admin['name'], 'username' => $admin['username']];
            unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);
            $return = (string) ($_GET['return'] ?? '');
            redirect($return !== '' && str_starts_with($return, '/') && !str_starts_with($return, '//') ? $return : url('admin/'));
        }
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['last_login_attempt'] = time();
        $error = 'The username/email or password is incorrect.';
    }
}
?>
<!doctype html>
<html lang="en" data-admin-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Admin login | Wisdom News</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(url('admin/assets/admin.css?v=' . ((string) (@filemtime(ROOT_PATH . '/admin/assets/admin.css') ?: 1)))) ?>" rel="stylesheet">
</head>
<body class="login-page">
  <main class="login-shell">
    <section class="login-aside" aria-label="Wisdom News newsroom">
      <a class="login-brand" href="<?= e(url()) ?>">
        <?php if (setting('site_logo')): ?><img src="<?= e(image_url(setting('site_logo'))) ?>" alt="<?= e(setting('site_name', APP_NAME)) ?>"><?php else: ?><span class="admin-brand-mark">W</span><strong>Wisdom News</strong><?php endif; ?>
      </a>
      <div class="login-message"><span class="page-eyebrow">EDITORIAL CONTROL</span><h1>Your newsroom, clearly organized.</h1><p>Manage stories, categories, publishing status, and your public identity from one focused workspace.</p></div>
      <small>Secure administration · Wisdom News</small>
    </section>
    <section class="login-card" aria-labelledby="login-title">
      <div class="login-card-header"><span class="page-eyebrow">WELCOME BACK</span><h2 id="login-title">Sign in to continue</h2><p>Use your administrator account to access the newsroom.</p></div>
      <?php if ($error): ?><div class="alert alert-danger admin-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post"><?= csrf_field() ?>
        <div class="mb-3"><label class="form-label required" for="identity">Username or email</label><input class="form-control form-control-lg" id="identity" name="identity" autocomplete="username" required autofocus></div>
        <div class="mb-4"><label class="form-label required" for="password">Password</label><input class="form-control form-control-lg" id="password" type="password" name="password" autocomplete="current-password" required></div>
        <button class="btn btn-success btn-lg w-100" type="submit">Sign in securely</button>
      </form>
      <a class="back-to-site" href="<?= e(url()) ?>"><span aria-hidden="true">←</span> Back to public website</a>
    </section>
  </main>
  <script src="<?= e(url('admin/assets/admin.js?v=' . ((string) (@filemtime(ROOT_PATH . '/admin/assets/admin.js') ?: 1)))) ?>"></script>
</body>
</html>
