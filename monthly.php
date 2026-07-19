<?php
/**
 * Excel-style monthly attendance sheet with color coding and per-employee totals.
 */
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle   = 'Monthly Attendance Sheet';
$activePage  = 'monthly';
$breadcrumbs = ['Monthly Sheet' => null];
$pageScripts = ['assets/js/monthly.js'];

$departments = db()->query('SELECT name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];

include __DIR__ . '/includes/header.php';
?>
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">Month</label>
                <select class="form-select" id="selMonth">
                    <?php foreach ($months as $n => $name): ?>
                        <option value="<?= $n ?>" <?= $n == (int)date('n') ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Year</label>
                <select class="form-select" id="selYear">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 4; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select class="form-select" id="selDept">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= e($d) ?>"><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 text-md-end">
                <button class="btn btn-outline-secondary" id="btnPrintMonthly"><i class="fa-solid fa-print me-1"></i>Print</button>
                <button class="btn btn-success" id="btnExportMonthly"><i class="fa-solid fa-file-excel me-1"></i>Excel</button>
            </div>
        </div>
        <div class="mt-3 d-flex flex-wrap gap-3 small">
            <span><span class="legend-box cell-P"></span> Present</span>
            <span><span class="legend-box cell-A"></span> Absent</span>
            <span><span class="legend-box cell-H"></span> Half Day</span>
            <span><span class="legend-box cell-L"></span> Leave</span>
            <span><span class="legend-box cell-O"></span> Holiday</span>
            <span><span class="legend-box cell-W"></span> Weekend</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-table-cells me-2"></i><span id="sheetTitle">Monthly Sheet</span></div>
    <div class="card-body">
        <div class="table-responsive" style="max-height:70vh">
            <table class="monthly-sheet" id="monthlySheet">
                <thead><tr id="sheetHead"></tr></thead>
                <tbody id="sheetBody"><tr><td class="text-center text-muted py-4">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
