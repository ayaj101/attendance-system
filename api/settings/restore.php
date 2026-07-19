<?php
/**
 * Restore the database from an uploaded .sql backup file.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['sql_file']['name'])) {
    json_response(['success' => false, 'message' => 'Please choose a .sql file to restore.'], 422);
}
if ($_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'message' => 'Upload failed.'], 422);
}
$ext = strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'sql') {
    json_response(['success' => false, 'message' => 'Only .sql files are allowed.'], 422);
}

$sql = file_get_contents($_FILES['sql_file']['tmp_name']);
if ($sql === false || trim($sql) === '') {
    json_response(['success' => false, 'message' => 'The backup file is empty or unreadable.'], 422);
}

try {
    db()->exec($sql);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()], 500);
}

log_activity('restore', 'Restored database from backup.');
json_response(['success' => true, 'message' => 'Database restored successfully.']);
