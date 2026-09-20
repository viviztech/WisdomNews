<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
if (!is_post()) {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
unset($_SESSION['admin']);
session_regenerate_id(true);
redirect(url('admin/login.php'));
