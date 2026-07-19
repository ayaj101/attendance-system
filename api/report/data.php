<?php
/**
 * Report data provider. Returns { columns, rows, title } for a given report
 * type with optional date-range / department / employee filters.
 *
 * Supported types: daily, weekly, monthly, employee, department, late, early,
 * overtime, leave, summary.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/report_query.php';
require_api_login();

$type = input('type', 'daily');
$filters = [
    'from'       => input('from', ''),
    'to'         => input('to', ''),
    'department' => input('department', ''),
    'employee'   => (int) input('employee', 0),
    'month'      => (int) input('month', (int) date('n')),
    'year'       => (int) input('year', (int) date('Y')),
    'date'       => input('date', date('Y-m-d')),
];

$report = build_report($type, $filters);
json_response(['success' => true] + $report);
