<?php
/**
 * List departments with a live employee count.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();

$sql = 'SELECT d.id, d.name,
               (SELECT COUNT(*) FROM employees e WHERE e.department = d.name) AS employee_count
        FROM departments d ORDER BY d.name';
$rows = db()->query($sql)->fetchAll();
json_response(['success' => true, 'data' => $rows]);
