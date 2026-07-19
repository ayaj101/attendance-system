<?php
/**
 * Export reports to CSV, Excel (.xls) or a print/PDF-friendly HTML page.
 *
 * Query params:
 *   type    - report type (see report_query.php) or "monthly" grid via view=sheet
 *   format  - csv | excel | pdf   (default excel)
 *   view    - "sheet" to export the Excel-style monthly grid
 *   + the usual report filters
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/report_query.php';
require_login();

$format = strtolower((string) input('format', 'excel'));
$view   = input('view', '');

if ($view === 'sheet') {
    $report = build_monthly_grid();
} else {
    $type = input('type', 'daily');
    $filters = [
        'from' => input('from', ''), 'to' => input('to', ''),
        'department' => input('department', ''), 'employee' => (int) input('employee', 0),
        'month' => (int) input('month', (int) date('n')),
        'year'  => (int) input('year', (int) date('Y')),
        'date'  => input('date', date('Y-m-d')),
    ];
    $report = build_report($type, $filters);
}

$title = $report['title'];
$columns = $report['columns'];
$rows = $report['rows'];
$slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($title));

switch ($format) {
    case 'csv':
        export_csv($slug, $columns, $rows);
        break;
    case 'pdf':
        export_pdf($title, $columns, $rows);
        break;
    case 'excel':
    default:
        export_excel($slug, $title, $columns, $rows);
        break;
}

/** Build the Excel-style monthly grid (employees x days). */
function build_monthly_grid(): array
{
    $year  = (int) input('year', (int) date('Y'));
    $month = (int) input('month', (int) date('n'));
    $department = input('department', '');
    $days = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

    $params = [];
    $sql = 'SELECT id, employee_code, name FROM employees WHERE status="Active"';
    if (!empty($department)) { $sql .= ' AND department = ?'; $params[] = $department; }
    $sql .= ' ORDER BY name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll();

    $attStmt = db()->prepare('SELECT employee_id, DAY(attendance_date) d, status FROM attendance WHERE YEAR(attendance_date)=? AND MONTH(attendance_date)=?');
    $attStmt->execute([$year, $month]);
    $map = [];
    $codeMap = ['Present'=>'P','Absent'=>'A','Half Day'=>'H','Leave'=>'L','Holiday'=>'O','Weekend'=>'W'];
    foreach ($attStmt->fetchAll() as $r) { $map[$r['employee_id']][(int)$r['d']] = $codeMap[$r['status']] ?? ''; }

    $columns = array_merge(['Code', 'Employee'], range(1, $days));
    $rows = [];
    foreach ($employees as $e) {
        $row = [$e['employee_code'], $e['name']];
        for ($d = 1; $d <= $days; $d++) { $row[] = $map[$e['id']][$d] ?? ''; }
        $rows[] = $row;
    }
    return [
        'title'   => 'Monthly Attendance Sheet - ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)),
        'columns' => $columns,
        'rows'    => $rows,
    ];
}

function export_csv(string $slug, array $columns, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $slug . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $columns);
    foreach ($rows as $r) { fputcsv($out, $r); }
    fclose($out);
    exit;
}

function export_excel(string $slug, string $title, array $columns, array $rows): void
{
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $slug . '.xls"');
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<h3>' . htmlspecialchars($title) . '</h3>';
    echo '<table border="1" cellspacing="0" cellpadding="4"><thead><tr>';
    foreach ($columns as $c) { echo '<th>' . htmlspecialchars((string) $c) . '</th>'; }
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($r as $cell) { echo '<td>' . htmlspecialchars((string) $cell) . '</td>'; }
        echo '</tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

function export_pdf(string $title, array $columns, array $rows): void
{
    // Lightweight, dependency-free PDF export: a print-optimised HTML page that
    // auto-opens the browser print dialog (choose "Save as PDF").
    header('Content-Type: text/html; charset=utf-8');
    $set = settings();
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>';
    echo '<style>
        body{font-family:Arial,Helvetica,sans-serif;margin:24px;color:#222}
        h2{margin:0 0 2px} .muted{color:#666;font-size:12px;margin-bottom:14px}
        table{border-collapse:collapse;width:100%;font-size:12px}
        th,td{border:1px solid #999;padding:5px 7px;text-align:left}
        thead{background:#4f46e5;color:#fff}
        tr:nth-child(even){background:#f3f4f6}
        @media print{.noprint{display:none}}
        .btn{background:#4f46e5;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;margin-bottom:14px}
    </style></head><body>';
    echo '<button class="btn noprint" onclick="window.print()">Print / Save as PDF</button>';
    echo '<h2>' . htmlspecialchars((string) ($set['company_name'] ?? 'Company')) . '</h2>';
    echo '<div class="muted">' . htmlspecialchars($title) . ' &middot; Generated ' . date('d M Y H:i') . '</div>';
    echo '<table><thead><tr>';
    foreach ($columns as $c) { echo '<th>' . htmlspecialchars((string) $c) . '</th>'; }
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($r as $cell) { echo '<td>' . htmlspecialchars((string) $cell) . '</td>'; }
        echo '</tr>';
    }
    echo '</tbody></table><script>window.onload=function(){setTimeout(window.print,400)}</script></body></html>';
    exit;
}
