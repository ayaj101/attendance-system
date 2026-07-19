<?php
/**
 * Reports hub: pick a report type, apply filters, view results and export to
 * Print / Excel / PDF / CSV.
 */
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle   = 'Reports';
$activePage  = 'reports';
$breadcrumbs = ['Reports' => null];
$pageScripts = ['assets/js/reports.js'];

$departments = db()->query('SELECT name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$employees   = db()->query('SELECT id, name, employee_code FROM employees WHERE status="Active" ORDER BY name')->fetchAll();
$months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];

$reportTypes = [
    'daily'      => 'Daily Attendance',
    'weekly'     => 'Weekly Attendance',
    'monthly'    => 'Monthly Attendance',
    'employee'   => 'Employee Attendance',
    'department' => 'Department Attendance',
    'late'       => 'Late Arrival',
    'early'      => 'Early Exit',
    'overtime'   => 'Overtime',
    'leave'      => 'Leave',
    'summary'    => 'Summary',
];

$prefill = e(input('q', ''));
include __DIR__ . '/includes/header.php';
?>
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select class="form-select" id="reportType">
                    <?php foreach ($reportTypes as $k => $label): ?>
                        <option value="<?= $k ?>"><?= $label ?> Report</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 filter-date">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" id="fDate" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-2 filter-range">
                <label class="form-label">From</label>
                <input type="date" class="form-control" id="fFrom">
            </div>
            <div class="col-md-2 filter-range">
                <label class="form-label">To</label>
                <input type="date" class="form-control" id="fTo">
            </div>
            <div class="col-md-2 filter-month">
                <label class="form-label">Month</label>
                <select class="form-select" id="fMonth">
                    <?php foreach ($months as $n => $name): ?>
                        <option value="<?= $n ?>" <?= $n==(int)date('n')?'selected':'' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 filter-month">
                <label class="form-label">Year</label>
                <select class="form-select" id="fYear">
                    <?php for ($y=(int)date('Y'); $y>=(int)date('Y')-4; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select class="form-select" id="fDept">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= e($d) ?>"><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee</label>
                <select class="form-select" id="fEmployee">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= (int)$emp['id'] ?>"><?= e($emp['name']) ?> (<?= e($emp['employee_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="btnGenerate"><i class="fa-solid fa-magnifying-glass me-1"></i>Generate</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span id="reportTitle"><i class="fa-solid fa-file-lines me-2"></i>Report</span>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" data-export="print"><i class="fa-solid fa-print me-1"></i>Print</button>
            <button class="btn btn-outline-success" data-export="excel"><i class="fa-solid fa-file-excel me-1"></i>Excel</button>
            <button class="btn btn-outline-danger" data-export="pdf"><i class="fa-solid fa-file-pdf me-1"></i>PDF</button>
            <button class="btn btn-outline-primary" data-export="csv"><i class="fa-solid fa-file-csv me-1"></i>CSV</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle" id="reportTable" style="width:100%">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>window.REPORT_PREFILL = "<?= $prefill ?>";</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
