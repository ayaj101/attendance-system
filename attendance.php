<?php
/**
 * Daily attendance entry. Admin picks a date, sees all active employees and
 * records status / in-time / out-time / remarks. Working hours & overtime are
 * computed live (JS) and re-validated on the server at save time.
 */
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle   = 'Daily Attendance';
$activePage  = 'attendance';
$breadcrumbs = ['Attendance' => null];
$pageScripts = ['assets/js/attendance.js'];

$set = settings();
$date = input('date', date('Y-m-d'));

include __DIR__ . '/includes/header.php';
?>
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-sm-4 col-md-3">
                <label class="form-label">Attendance Date</label>
                <input type="date" class="form-control" id="attDate" value="<?= e($date) ?>">
            </div>
            <div class="col-sm-4 col-md-3">
                <label class="form-label">Bulk mark all as</label>
                <select class="form-select" id="bulkStatus">
                    <option value="">Select...</option>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Half Day">Half Day</option>
                    <option value="Leave">Leave</option>
                    <option value="Holiday">Holiday</option>
                    <option value="Weekend">Weekend</option>
                </select>
            </div>
            <div class="col-sm-4 col-md-3">
                <label class="form-label">Search employee</label>
                <input type="text" class="form-control" id="attSearch" placeholder="Name or code...">
            </div>
            <div class="col-md-3 text-md-end">
                <button class="btn btn-success w-100 w-md-auto" id="btnSaveAttendance">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Attendance
                </button>
            </div>
        </div>
        <div class="small text-muted mt-2">
            Office: <strong><?= e(substr((string)$set['office_start'],0,5)) ?></strong>–<strong><?= e(substr((string)$set['office_end'],0,5)) ?></strong>
            &middot; Duty hours: <strong><?= e($set['duty_hours']) ?></strong>
            &middot; Break: <strong><?= (int)$set['break_time'] ?> min</strong>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" id="attendanceTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px">S.No</th>
                        <th>Employee</th>
                        <th style="width:140px">Status</th>
                        <th style="width:120px">In Time</th>
                        <th style="width:120px">Out Time</th>
                        <th style="width:110px" class="text-center">Working</th>
                        <th style="width:110px" class="text-center">Overtime</th>
                        <th style="width:180px">Remarks</th>
                    </tr>
                </thead>
                <tbody id="attendanceBody">
                    <tr><td colspan="8" class="text-center text-muted py-4">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.ATT_CONFIG = {
        breakTime: <?= (int) $set['break_time'] ?>,
        dutyHours: "<?= e($set['duty_hours']) ?>",
        officeStart: "<?= e(substr((string)$set['office_start'],0,5)) ?>",
        officeEnd: "<?= e(substr((string)$set['office_end'],0,5)) ?>"
    };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
