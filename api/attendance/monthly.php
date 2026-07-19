<?php
/**
 * Excel-style monthly attendance matrix: one row per employee, one column per
 * day, plus per-employee summary totals.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();

$year  = (int) input('year', (int) date('Y'));
$month = (int) input('month', (int) date('n'));
$department = input('department', '');
if ($month < 1 || $month > 12) { $month = (int) date('n'); }

$daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

$empSql = 'SELECT id, employee_code, name, department FROM employees WHERE status = "Active"';
$params = [];
if ($department !== '' && $department !== null) {
    $empSql .= ' AND department = ?';
    $params[] = $department;
}
$empSql .= ' ORDER BY name';
$stmt = db()->prepare($empSql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Fetch all attendance for the month in one query.
$attStmt = db()->prepare(
    'SELECT employee_id, DAY(attendance_date) d, status, working_hours, overtime
     FROM attendance WHERE YEAR(attendance_date)=? AND MONTH(attendance_date)=?'
);
$attStmt->execute([$year, $month]);
$map = [];
foreach ($attStmt->fetchAll() as $r) {
    $map[$r['employee_id']][(int) $r['d']] = $r;
}

$codeMap = ['Present' => 'P', 'Absent' => 'A', 'Half Day' => 'H', 'Leave' => 'L', 'Holiday' => 'O', 'Weekend' => 'W'];

$rows = [];
foreach ($employees as $emp) {
    $days = [];
    $summary = ['P' => 0, 'A' => 0, 'H' => 0, 'L' => 0, 'workMin' => 0, 'otMin' => 0];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $rec = $map[$emp['id']][$d] ?? null;
        $code = $rec ? ($codeMap[$rec['status']] ?? '') : '';
        $days[$d] = $code;
        if ($rec) {
            if ($code === 'P') { $summary['P']++; }
            elseif ($code === 'A') { $summary['A']++; }
            elseif ($code === 'H') { $summary['H']++; }
            elseif ($code === 'L') { $summary['L']++; }
            $summary['workMin'] += (int) time_to_minutes($rec['working_hours']);
            $summary['otMin']   += (int) time_to_minutes($rec['overtime']);
        }
    }
    $rows[] = [
        'id'           => (int) $emp['id'],
        'code'         => $emp['employee_code'],
        'name'         => $emp['name'],
        'department'   => $emp['department'],
        'days'         => $days,
        'present'      => $summary['P'],
        'absent'       => $summary['A'],
        'halfday'      => $summary['H'],
        'leave'        => $summary['L'],
        'total_hours'  => minutes_to_hhmm($summary['workMin']),
        'total_ot'     => minutes_to_hhmm($summary['otMin']),
    ];
}

json_response([
    'success'       => true,
    'days_in_month' => $daysInMonth,
    'year'          => $year,
    'month'         => $month,
    'month_name'    => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
    'rows'          => $rows,
]);
