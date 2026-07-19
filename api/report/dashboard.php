<?php
/**
 * Aggregated dashboard statistics + chart datasets.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();

$pdo   = db();
$today = date('Y-m-d');
$year  = (int) date('Y');
$month = (int) date('n');

/** Count today's attendance by status. */
$todayCounts = ['Present' => 0, 'Absent' => 0, 'Half Day' => 0, 'Leave' => 0, 'Holiday' => 0, 'Weekend' => 0];
$stmt = $pdo->prepare('SELECT status, COUNT(*) c FROM attendance WHERE attendance_date = ? GROUP BY status');
$stmt->execute([$today]);
foreach ($stmt->fetchAll() as $row) {
    $todayCounts[$row['status']] = (int) $row['c'];
}

$totalEmployees = (int) $pdo->query('SELECT COUNT(*) FROM employees WHERE status = "Active"')->fetchColumn();
$totalDepartments = (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();

/** Total overtime today (minutes -> HH:MM). */
$stmt = $pdo->prepare('SELECT COALESCE(SUM(TIME_TO_SEC(overtime)),0)/60 AS mins FROM attendance WHERE attendance_date = ?');
$stmt->execute([$today]);
$otMinsToday = (int) round((float) $stmt->fetchColumn());

/** Monthly attendance percentage (present + half-day counted). */
$stmt = $pdo->prepare(
    'SELECT
        SUM(status IN ("Present","Half Day")) AS present_like,
        SUM(status IN ("Present","Absent","Half Day","Leave")) AS working_total
     FROM attendance
     WHERE YEAR(attendance_date) = ? AND MONTH(attendance_date) = ?'
);
$stmt->execute([$year, $month]);
$m = $stmt->fetch();
$monthlyPct = ($m['working_total'] ?? 0) > 0
    ? round(($m['present_like'] / $m['working_total']) * 100, 1)
    : 0;

/** Average working hours this month. */
$stmt = $pdo->prepare(
    'SELECT COALESCE(AVG(TIME_TO_SEC(working_hours)),0)/3600 AS avg_h
     FROM attendance WHERE status IN ("Present","Half Day")
       AND YEAR(attendance_date)=? AND MONTH(attendance_date)=?'
);
$stmt->execute([$year, $month]);
$avgWorkingHours = round((float) $stmt->fetchColumn(), 2);

/** Total overtime hours this month. */
$stmt = $pdo->prepare(
    'SELECT COALESCE(SUM(TIME_TO_SEC(overtime)),0)/3600 AS h
     FROM attendance WHERE YEAR(attendance_date)=? AND MONTH(attendance_date)=?'
);
$stmt->execute([$year, $month]);
$monthlyOtHours = round((float) $stmt->fetchColumn(), 1);

/** Daily attendance trend (present count per day this month). */
$stmt = $pdo->prepare(
    'SELECT DAY(attendance_date) d, SUM(status IN ("Present","Half Day")) present
     FROM attendance WHERE YEAR(attendance_date)=? AND MONTH(attendance_date)=?
     GROUP BY DAY(attendance_date) ORDER BY d'
);
$stmt->execute([$year, $month]);
$trendLabels = [];
$trendData = [];
foreach ($stmt->fetchAll() as $r) {
    $trendLabels[] = (int) $r['d'];
    $trendData[] = (int) $r['present'];
}

/** Overtime trend (total OT hours per day this month). */
$stmt = $pdo->prepare(
    'SELECT DAY(attendance_date) d, COALESCE(SUM(TIME_TO_SEC(overtime)),0)/3600 h
     FROM attendance WHERE YEAR(attendance_date)=? AND MONTH(attendance_date)=?
     GROUP BY DAY(attendance_date) ORDER BY d'
);
$stmt->execute([$year, $month]);
$otLabels = [];
$otData = [];
foreach ($stmt->fetchAll() as $r) {
    $otLabels[] = (int) $r['d'];
    $otData[] = round((float) $r['h'], 1);
}

/** Monthly attendance breakdown (status distribution this month). */
$stmt = $pdo->prepare(
    'SELECT status, COUNT(*) c FROM attendance
     WHERE YEAR(attendance_date)=? AND MONTH(attendance_date)=? GROUP BY status'
);
$stmt->execute([$year, $month]);
$breakdown = ['Present' => 0, 'Absent' => 0, 'Half Day' => 0, 'Leave' => 0, 'Holiday' => 0, 'Weekend' => 0];
foreach ($stmt->fetchAll() as $r) {
    $breakdown[$r['status']] = (int) $r['c'];
}

/** Recent activity. */
$recent = $pdo->query(
    'SELECT al.action, al.description, al.created_at, u.name
     FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id
     ORDER BY al.id DESC LIMIT 8'
)->fetchAll();

json_response([
    'success' => true,
    'cards' => [
        'total_employees'  => $totalEmployees,
        'present_today'    => $todayCounts['Present'],
        'absent_today'     => $todayCounts['Absent'],
        'halfday_today'    => $todayCounts['Half Day'],
        'leave_today'      => $todayCounts['Leave'],
        'overtime_today'   => minutes_to_hhmm($otMinsToday),
        'departments'      => $totalDepartments,
        'monthly_pct'      => $monthlyPct,
        'avg_working'      => $avgWorkingHours,
        'monthly_ot_hours' => $monthlyOtHours,
    ],
    'charts' => [
        'trend'     => ['labels' => $trendLabels, 'data' => $trendData],
        'overtime'  => ['labels' => $otLabels, 'data' => $otData],
        'breakdown' => ['labels' => array_keys($breakdown), 'data' => array_values($breakdown)],
    ],
    'recent' => $recent,
    'month'  => date('F Y'),
]);
