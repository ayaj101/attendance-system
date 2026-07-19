<?php
/**
 * Persist the light/dark theme preference.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();
require_csrf();

$theme = input('theme', 'light');
if (!in_array($theme, ['light', 'dark'], true)) {
    json_response(['success' => false, 'message' => 'Invalid theme.'], 422);
}
$id = (int) db()->query('SELECT id FROM settings ORDER BY id ASC LIMIT 1')->fetchColumn();
if ($id) {
    db()->prepare('UPDATE settings SET theme=? WHERE id=?')->execute([$theme, $id]);
}
json_response(['success' => true, 'theme' => $theme]);
