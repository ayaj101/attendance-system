<?php
/**
 * Create or rename a department. Renaming also updates linked employees.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}
$id   = (int) input('id', 0);
$name = input('name', '');
if ($name === '') {
    json_response(['success' => false, 'message' => 'Department name is required.', 'errors' => ['name' => 'Required.']], 422);
}
$chk = db()->prepare('SELECT id FROM departments WHERE name = ? AND id <> ?');
$chk->execute([$name, $id]);
if ($chk->fetch()) {
    json_response(['success' => false, 'message' => 'Department already exists.', 'errors' => ['name' => 'Already exists.']], 422);
}

if ($id > 0) {
    $old = db()->prepare('SELECT name FROM departments WHERE id = ?');
    $old->execute([$id]);
    $oldName = $old->fetchColumn();
    db()->prepare('UPDATE departments SET name = ? WHERE id = ?')->execute([$name, $id]);
    if ($oldName && $oldName !== $name) {
        db()->prepare('UPDATE employees SET department = ? WHERE department = ?')->execute([$name, $oldName]);
    }
    log_activity('department_update', "Renamed department to {$name}");
    json_response(['success' => true, 'message' => 'Department updated.']);
}
db()->prepare('INSERT INTO departments (name) VALUES (?)')->execute([$name]);
log_activity('department_create', "Added department: {$name}");
json_response(['success' => true, 'message' => 'Department added.']);
