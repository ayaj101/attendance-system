<?php
/**
 * Return employees as JSON (with optional department/status filters).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();

$department = input('department', '');
$status     = input('status', '');
$search     = input('q', '');

$sql = 'SELECT * FROM employees WHERE 1=1';
$params = [];
if ($department !== '' && $department !== null) {
    $sql .= ' AND department = ?';
    $params[] = $department;
}
if ($status !== '' && $status !== null) {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
if ($search !== '' && $search !== null) {
    $sql .= ' AND (name LIKE ? OR employee_code LIKE ? OR email LIKE ? OR mobile LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql .= ' ORDER BY id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

json_response(['success' => true, 'data' => $rows]);
