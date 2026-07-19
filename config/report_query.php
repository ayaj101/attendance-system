<?php
/**
 * Shared report query builder used by both the JSON data endpoint and the
 * export endpoint so the two never drift apart.
 */
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Build a report as an array of ['title' => .., 'columns' => [..], 'rows' => [[..]]].
 *
 * @param array<string,mixed> $f Filters.
 * @return array{title:string, columns:array<int,string>, rows:array<int,array<int,string>>}
 */
function build_report(string $type, array $f): array
{
    $pdo = db();

    // Resolve a default date range where relevant.
    $from = $f['from'] ?: null;
    $to   = $f['to'] ?: null;
    if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = null; }
    if ($to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to = null; }

    switch ($type) {
        case 'daily':
            return report_daily($pdo, $f);
        case 'weekly':
            return report_range($pdo, $f, 'Weekly Attendance Report', $from ?: date('Y-m-d', strtotime('monday this week')), $to ?: date('Y-m-d', strtotime('sunday this week')));
        case 'monthly':
            $start = sprintf('%04d-%02d-01', $f['year'], $f['month']);
            $end   = date('Y-m-t', strtotime($start));
            return report_range($pdo, $f, 'Monthly Attendance Report - ' . date('F Y', strtotime($start)), $start, $end);
        case 'employee':
            return report_employee($pdo, $f);
        case 'department':
            return report_department($pdo, $f);
        case 'late':
            return report_late_early($pdo, $f, 'late');
        case 'early':
            return report_late_early($pdo, $f, 'early');
        case 'overtime':
            return report_overtime($pdo, $f, $from, $to);
        case 'leave':
            return report_leave($pdo, $f, $from, $to);
        case 'summary':
        default:
            return report_summary($pdo, $f);
    }
}

/** Common filter clause + params for attendance queries. */
function att_filters(array $f, array &$params, string $alias = 'a', string $empAlias = 'e'): string
{
    $sql = '';
    if (!empty($f['department'])) {
        $sql .= " AND {$empAlias}.department = ?";
        $params[] = $f['department'];
    }
    if (!empty($f['employee'])) {
        $sql .= " AND {$empAlias}.id = ?";
        $params[] = (int) $f['employee'];
    }
    return $sql;
}

function report_daily(PDO $pdo, array $f): array
{
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $f['date']) ? $f['date'] : date('Y-m-d');
    $params = [$date];
    $where = att_filters($f, $params);
    $sql = "SELECT e.employee_code, e.name, e.department, a.status, a.in_time, a.out_time,
                   a.working_hours, a.overtime, a.remarks
            FROM employees e
            LEFT JOIN attendance a ON a.employee_id = e.id AND a.attendance_date = ?
            WHERE e.status='Active' {$where}
            ORDER BY e.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map(fn($r) => [
        $r['employee_code'], $r['name'], $r['department'] ?? '-',
        $r['status'] ?? 'Not Marked', fmt_time($r['in_time']), fmt_time($r['out_time']),
        $r['working_hours'] ?? '00:00', $r['overtime'] ?? '00:00', $r['remarks'] ?? '',
    ], $stmt->fetchAll());
    return [
        'title'   => 'Daily Attendance Report - ' . date('d M Y', strtotime($date)),
        'columns' => ['Code', 'Employee', 'Department', 'Status', 'In', 'Out', 'Working', 'Overtime', 'Remarks'],
        'rows'    => $rows,
    ];
}

function report_range(PDO $pdo, array $f, string $title, string $start, string $end): array
{
    $params = [$start, $end];
    $where = att_filters($f, $params);
    $sql = "SELECT a.attendance_date, e.employee_code, e.name, e.department, a.status,
                   a.in_time, a.out_time, a.working_hours, a.overtime, a.remarks
            FROM attendance a JOIN employees e ON e.id = a.employee_id
            WHERE a.attendance_date BETWEEN ? AND ? {$where}
            ORDER BY a.attendance_date, e.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map(fn($r) => [
        date('d M Y', strtotime($r['attendance_date'])), $r['employee_code'], $r['name'],
        $r['department'] ?? '-', $r['status'], fmt_time($r['in_time']), fmt_time($r['out_time']),
        $r['working_hours'], $r['overtime'], $r['remarks'] ?? '',
    ], $stmt->fetchAll());
    return [
        'title'   => $title . ' (' . date('d M', strtotime($start)) . ' - ' . date('d M Y', strtotime($end)) . ')',
        'columns' => ['Date', 'Code', 'Employee', 'Department', 'Status', 'In', 'Out', 'Working', 'Overtime', 'Remarks'],
        'rows'    => $rows,
    ];
}

function report_employee(PDO $pdo, array $f): array
{
    $start = sprintf('%04d-%02d-01', $f['year'], $f['month']);
    $end   = date('Y-m-t', strtotime($start));
    $params = [$start, $end];
    $where = att_filters($f, $params);
    $sql = "SELECT e.employee_code, e.name, e.department,
                   SUM(a.status='Present') p, SUM(a.status='Absent') ab,
                   SUM(a.status='Half Day') h, SUM(a.status='Leave') l,
                   SEC_TO_TIME(SUM(TIME_TO_SEC(a.working_hours))) wh,
                   SEC_TO_TIME(SUM(TIME_TO_SEC(a.overtime))) ot
            FROM employees e LEFT JOIN attendance a
                 ON a.employee_id=e.id AND a.attendance_date BETWEEN ? AND ?
            WHERE e.status='Active' {$where}
            GROUP BY e.id ORDER BY e.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map(fn($r) => [
        $r['employee_code'], $r['name'], $r['department'] ?? '-',
        (int) $r['p'], (int) $r['ab'], (int) $r['h'], (int) $r['l'],
        hms_to_hhmm($r['wh']), hms_to_hhmm($r['ot']),
    ], $stmt->fetchAll());
    return [
        'title'   => 'Employee Attendance Report - ' . date('F Y', strtotime($start)),
        'columns' => ['Code', 'Employee', 'Department', 'Present', 'Absent', 'Half Day', 'Leave', 'Working Hrs', 'Overtime'],
        'rows'    => $rows,
    ];
}

function report_department(PDO $pdo, array $f): array
{
    $start = sprintf('%04d-%02d-01', $f['year'], $f['month']);
    $end   = date('Y-m-t', strtotime($start));
    $sql = "SELECT e.department,
                   COUNT(DISTINCT e.id) emps,
                   SUM(a.status='Present') p, SUM(a.status='Absent') ab,
                   SUM(a.status='Half Day') h, SUM(a.status='Leave') l,
                   SEC_TO_TIME(SUM(TIME_TO_SEC(a.overtime))) ot
            FROM employees e LEFT JOIN attendance a
                 ON a.employee_id=e.id AND a.attendance_date BETWEEN ? AND ?
            WHERE e.status='Active'
            GROUP BY e.department ORDER BY e.department";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start, $end]);
    $rows = array_map(fn($r) => [
        $r['department'] ?: '-', (int) $r['emps'], (int) $r['p'], (int) $r['ab'],
        (int) $r['h'], (int) $r['l'], hms_to_hhmm($r['ot']),
    ], $stmt->fetchAll());
    return [
        'title'   => 'Department Attendance Report - ' . date('F Y', strtotime($start)),
        'columns' => ['Department', 'Employees', 'Present', 'Absent', 'Half Day', 'Leave', 'Overtime'],
        'rows'    => $rows,
    ];
}

function report_late_early(PDO $pdo, array $f, string $mode): array
{
    $set = settings();
    $start = $f['from'] ?: sprintf('%04d-%02d-01', $f['year'], $f['month']);
    $end   = $f['to'] ?: date('Y-m-t', strtotime($start));
    $params = [$start, $end];
    $where = att_filters($f, $params);
    // Grace of a few minutes handled by comparing to office start/end.
    if ($mode === 'late') {
        $params[] = $set['office_start'];
        $cond = 'a.in_time > ?';
        $title = 'Late Arrival Report';
        $col = 'In Time';
    } else {
        $params[] = $set['office_end'];
        $cond = 'a.out_time < ? AND a.out_time IS NOT NULL';
        $title = 'Early Exit Report';
        $col = 'Out Time';
    }
    $sql = "SELECT a.attendance_date, e.employee_code, e.name, e.department, a.in_time, a.out_time
            FROM attendance a JOIN employees e ON e.id=a.employee_id
            WHERE a.attendance_date BETWEEN ? AND ? AND a.status IN ('Present','Half Day') {$where} AND {$cond}
            ORDER BY a.attendance_date DESC, e.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map(fn($r) => [
        date('d M Y', strtotime($r['attendance_date'])), $r['employee_code'], $r['name'],
        $r['department'] ?? '-', fmt_time($mode === 'late' ? $r['in_time'] : $r['out_time']),
    ], $stmt->fetchAll());
    return [
        'title'   => $title,
        'columns' => ['Date', 'Code', 'Employee', 'Department', $col],
        'rows'    => $rows,
    ];
}

function report_overtime(PDO $pdo, array $f, ?string $from, ?string $to): array
{
    $start = $from ?: sprintf('%04d-%02d-01', $f['year'], $f['month']);
    $end   = $to ?: date('Y-m-t', strtotime($start));
    $params = [$start, $end];
    $where = att_filters($f, $params);
    $sql = "SELECT e.employee_code, e.name, e.department,
                   SEC_TO_TIME(SUM(TIME_TO_SEC(a.overtime))) ot,
                   COUNT(CASE WHEN TIME_TO_SEC(a.overtime) > 0 THEN 1 END) ot_days
            FROM attendance a JOIN employees e ON e.id=a.employee_id
            WHERE a.attendance_date BETWEEN ? AND ? {$where}
            GROUP BY e.id HAVING SUM(TIME_TO_SEC(a.overtime)) > 0
            ORDER BY SUM(TIME_TO_SEC(a.overtime)) DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map(fn($r) => [
        $r['employee_code'], $r['name'], $r['department'] ?? '-',
        (int) $r['ot_days'], hms_to_hhmm($r['ot']),
    ], $stmt->fetchAll());
    return [
        'title'   => 'Overtime Report (' . date('d M', strtotime($start)) . ' - ' . date('d M Y', strtotime($end)) . ')',
        'columns' => ['Code', 'Employee', 'Department', 'OT Days', 'Total Overtime'],
        'rows'    => $rows,
    ];
}

function report_leave(PDO $pdo, array $f, ?string $from, ?string $to): array
{
    $start = $from ?: sprintf('%04d-%02d-01', $f['year'], $f['month']);
    $end   = $to ?: date('Y-m-t', strtotime($start));
    $params = [$start, $end];
    $where = att_filters($f, $params);
    $sql = "SELECT a.attendance_date, e.employee_code, e.name, e.department, a.remarks
            FROM attendance a JOIN employees e ON e.id=a.employee_id
            WHERE a.attendance_date BETWEEN ? AND ? AND a.status='Leave' {$where}
            ORDER BY a.attendance_date DESC, e.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map(fn($r) => [
        date('d M Y', strtotime($r['attendance_date'])), $r['employee_code'],
        $r['name'], $r['department'] ?? '-', $r['remarks'] ?? '',
    ], $stmt->fetchAll());
    return [
        'title'   => 'Leave Report',
        'columns' => ['Date', 'Code', 'Employee', 'Department', 'Reason'],
        'rows'    => $rows,
    ];
}

function report_summary(PDO $pdo, array $f): array
{
    $start = sprintf('%04d-%02d-01', $f['year'], $f['month']);
    $end   = date('Y-m-t', strtotime($start));
    $stmt = $pdo->prepare(
        "SELECT status, COUNT(*) c FROM attendance
         WHERE attendance_date BETWEEN ? AND ? GROUP BY status"
    );
    $stmt->execute([$start, $end]);
    $counts = [];
    foreach ($stmt->fetchAll() as $r) { $counts[$r['status']] = (int) $r['c']; }

    $totalEmp = (int) $pdo->query('SELECT COUNT(*) FROM employees WHERE status="Active"')->fetchColumn();
    $otStmt = $pdo->prepare('SELECT SEC_TO_TIME(COALESCE(SUM(TIME_TO_SEC(overtime)),0)) FROM attendance WHERE attendance_date BETWEEN ? AND ?');
    $otStmt->execute([$start, $end]);
    $totalOt = hms_to_hhmm($otStmt->fetchColumn());

    $rows = [
        ['Active Employees', (string) $totalEmp],
        ['Total Present', (string) ($counts['Present'] ?? 0)],
        ['Total Absent', (string) ($counts['Absent'] ?? 0)],
        ['Total Half Day', (string) ($counts['Half Day'] ?? 0)],
        ['Total Leave', (string) ($counts['Leave'] ?? 0)],
        ['Total Holiday', (string) ($counts['Holiday'] ?? 0)],
        ['Total Weekend', (string) ($counts['Weekend'] ?? 0)],
        ['Total Overtime', $totalOt],
    ];
    return [
        'title'   => 'Summary Report - ' . date('F Y', strtotime($start)),
        'columns' => ['Metric', 'Value'],
        'rows'    => $rows,
    ];
}

/** Format a TIME (HH:MM:SS) to HH:MM, or dash if empty. */
function fmt_time(?string $t): string
{
    return $t ? substr($t, 0, 5) : '-';
}

/** Convert a SEC_TO_TIME result (which can exceed 24h) to HH:MM. */
function hms_to_hhmm(?string $t): string
{
    if (!$t) { return '00:00'; }
    $parts = explode(':', $t);
    return sprintf('%02d:%02d', (int) $parts[0], (int) ($parts[1] ?? 0));
}
