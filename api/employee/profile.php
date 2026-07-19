<?php
/**
 * Employee profile data: basic info + monthly/yearly summary + recent history.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();

$id = (int) input('id', 0);
if ($id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid employee id.'], 422);
}
$pdo = db();
$emp = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
$emp->execute([$id]);
$employee = $emp->fetch();
if (!$employee) {
    json_response(['success' => false, 'message' => 'Employee not found.'], 404);
}

$set = settings();
$year  = (int) input('year', (int) date('Y'));
$month = (int) input('month', (int) date('n'));
$mStart = sprintf('%04d-%02d-01', $year, $month);
$mEnd   = date('Y-m-t', strtotime($mStart));

/** Aggregate helper for a date range. */
$summary = function (string $start, string $end) use ($pdo, $id, $set): array {
    $stmt = $pdo->prepare(
        'SELECT
            SUM(status="Present") p, SUM(status="Absent") a,
            SUM(status="Half Day") h, SUM(status="Leave") l,
            SEC_TO_TIME(COALESCE(SUM(TIME_TO_SEC(working_hours)),0)) wh,
            SEC_TO_TIME(COALESCE(SUM(TIME_TO_SEC(overtime)),0)) ot,
            SUM(status IN ("Present","Half Day") AND in_time > ?) late,
            SUM(status IN ("Present","Half Day") AND out_time < ? AND out_time IS NOT NULL) early
         FROM attendance WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?'
    );
    $stmt->execute([$set['office_start'], $set['office_end'], $id, $start, $end]);
    $r = $stmt->fetch();
    $hhmm = fn($t) => $t ? sprintf('%02d:%02d', ...array_map('intval', array_slice(explode(':', $t), 0, 2))) : '00:00';
    return [
        'present' => (int) $r['p'], 'absent' => (int) $r['a'], 'halfday' => (int) $r['h'],
        'leave' => (int) $r['l'], 'late' => (int) $r['late'], 'early' => (int) $r['early'],
        'working_hours' => $hhmm($r['wh']), 'overtime' => $hhmm($r['ot']),
    ];
};

$monthly = $summary($mStart, $mEnd);
$yearly  = $summary(sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year));

// Recent attendance history (last 30 records).
$hist = $pdo->prepare(
    'SELECT attendance_date, status, in_time, out_time, working_hours, overtime, remarks
     FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT 30'
);
$hist->execute([$id]);

json_response([
    'success'  => true,
    'employee' => $employee,
    'monthly'  => $monthly,
    'yearly'   => $yearly,
    'history'  => $hist->fetchAll(),
    'month_name' => date('F Y', strtotime($mStart)),
    'year'     => $year,
]);
