<?php
/**
 * Log the user out and return to the login screen.
 */
require_once __DIR__ . '/config/auth.php';
logout();
header('Location: ' . url('login.php'));
exit;
