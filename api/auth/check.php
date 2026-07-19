<?php
/**
 * Lightweight session check endpoint (used to detect expired sessions in AJAX).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
login_from_cookie();
json_response([
    'success'      => true,
    'authenticated' => is_logged_in(),
    'user'         => current_user(),
]);
