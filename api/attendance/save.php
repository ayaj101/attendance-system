<?php
/**
 * Save (upsert) attendance for a whole day in one request.
 *
 * Expects JSON-ish POST: date + records[] where each record has
 * employee_id, status, in_time, out_time, remarks. Working hours and overtime
 * are recalculated server-side (never trust the client).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$date = input('date', '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
    json_response(['success' => false, 'message' => 'Invalid date.'], 422);
}

$records = $_POST['records'] ?? [];
if (is_string($records)) {
    $records = json_decode($records, true) ?: [];
}
if (!is_array($records) || !$records) {
    json_response(['success' => false, 'message' => 'No attendance records received.'], 422);
}

$validStatus = ['Present', 'Absent', 'Half Day', 'Leave', 'Holiday', 'Weekend'];
$pdo = db();
$stmt = $pdo->prepare(
    'INSERT INTO attendance (employee_id, attendance_date, status, in_time, out_time, working_hours, overtime, remarks)
     VALUES (:eid, :date, :status, :in, :out, :wh, :ot, :remarks)
     ON DUPLICATE KEY UPDATE
        status = VALUES(status), in_time = VALUES(in_time), out_time = VALUES(out_time),
        working_hours = VALUES(working_hours), overtime = VALUES(overtime), remarks = VALUES(remarks)'
);

$saved = 0;
$pdo->beginTransaction();
try {
    foreach ($records as $r) {
        $eid = (int) ($r['employee_id'] ?? 0);
        if ($eid <= 0) { continue; }
        $status = in_array($r['status'] ?? '', $validStatus, true) ? $r['status'] : 'Present';
        $in  = normalize_time($r['in_time'] ?? '');
        $out = normalize_time($r['out_time'] ?? '');
        $remarks = trim((string) ($r['remarks'] ?? ''));
        if (mb_strlen($remarks) > 255) { $remarks = mb_substr($remarks, 0, 255); }

        $calc = calculate_hours($in, $out, $status);

        $stmt->execute([
            ':eid'     => $eid,
            ':date'    => $date,
            ':status'  => $status,
            ':in'      => $in ?: null,
            ':out'     => $out ?: null,
            ':wh'      => $calc['working'],
            ':ot'      => $calc['overtime'],
            ':remarks' => $remarks,
        ]);
        $saved++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
}

log_activity('attendance_save', "Saved attendance for {$date} ({$saved} records)");
json_response(['success' => true, 'message' => "Attendance saved for {$saved} employees."]);

/**
 * Normalise a time value to HH:MM (or empty string).
 */
function normalize_time($value): string
{
    $value = trim((string) $value);
    if ($value === '') { return ''; }
    if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $m)) {
        return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
    }
    return '';
}
