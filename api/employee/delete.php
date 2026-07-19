<?php
/**
 * Delete an employee (and their attendance via FK cascade).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}
$id = (int) input('id', 0);
if ($id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid employee id.'], 422);
}
$stmt = db()->prepare('SELECT name, employee_code FROM employees WHERE id = ?');
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    json_response(['success' => false, 'message' => 'Employee not found.'], 404);
}
db()->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);
log_activity('employee_delete', "Deleted employee: {$emp['name']} ({$emp['employee_code']})");
json_response(['success' => true, 'message' => 'Employee deleted successfully.']);
