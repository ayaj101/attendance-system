<?php
/**
 * Return all active employees for a given date, joined with any existing
 * attendance record so the admin can review and edit the day at a glance.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();

$date = input('date', date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
    $date = date('Y-m-d');
}

$sql = 'SELECT e.id AS employee_id, e.employee_code, e.name, e.department,
               a.status, a.in_time, a.out_time, a.working_hours, a.overtime, a.remarks
        FROM employees e
        LEFT JOIN attendance a
               ON a.employee_id = e.id AND a.attendance_date = ?
        WHERE e.status = "Active"
        ORDER BY e.name';
$stmt = db()->prepare($sql);
$stmt->execute([$date]);
$rows = $stmt->fetchAll();

// Default weekend status suggestion (Sunday).
$isSunday = ((int) date('N', strtotime($date))) === 7;

json_response([
    'success'   => true,
    'date'      => $date,
    'is_sunday' => $isSunday,
    'data'      => $rows,
]);
