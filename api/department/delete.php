<?php
/**
 * Delete a department (only when no employees are assigned to it).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}
$id = (int) input('id', 0);
$stmt = db()->prepare('SELECT name FROM departments WHERE id = ?');
$stmt->execute([$id]);
$name = $stmt->fetchColumn();
if (!$name) {
    json_response(['success' => false, 'message' => 'Department not found.'], 404);
}
$count = db()->prepare('SELECT COUNT(*) FROM employees WHERE department = ?');
$count->execute([$name]);
if ((int) $count->fetchColumn() > 0) {
    json_response(['success' => false, 'message' => 'Cannot delete: employees are assigned to this department.'], 409);
}
db()->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
log_activity('department_delete', "Deleted department: {$name}");
json_response(['success' => true, 'message' => 'Department deleted.']);
