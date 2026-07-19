<?php
/**
 * Return a single employee by id.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();

$id = (int) input('id', 0);
if ($id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid employee id.'], 422);
}
$stmt = db()->prepare('SELECT * FROM employees WHERE id = ?');
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    json_response(['success' => false, 'message' => 'Employee not found.'], 404);
}
json_response(['success' => true, 'data' => $emp]);
