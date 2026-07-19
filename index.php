<?php
/**
 * Entry point: send visitors to the dashboard (if logged in) or the login page.
 */
require_once __DIR__ . '/config/auth.php';
login_from_cookie();
header('Location: ' . url(is_logged_in() ? 'dashboard.php' : 'login.php'));
exit;
