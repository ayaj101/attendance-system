<?php
/**
 * Employee profile: basic info, monthly & yearly summaries, attendance history.
 */
require_once __DIR__ . '/config/auth.php';
require_login();

$id = (int) input('id', 0);
$stmt = db()->prepare('SELECT * FROM employees WHERE id = ?');
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    $pageTitle = 'Employee Not Found';
    $activePage = 'employees';
    include __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-warning">Employee not found. <a href="' . url('employees.php') . '">Back to list</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle   = 'Employee Profile';
$activePage  = 'employees';
$breadcrumbs = ['Employees' => url('employees.php'), $emp['name'] => null];
$pageScripts = ['assets/js/profile.js'];

include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($emp['photo'])): ?>
                    <img src="<?= url($emp['photo']) ?>" class="rounded mb-3" style="width:110px;height:110px;object-fit:cover">
                <?php else: ?>
                    <span class="avatar mb-3" style="width:110px;height:110px;font-size:2.6rem"><?= e(strtoupper(substr($emp['name'],0,1))) ?></span>
                <?php endif; ?>
                <h5 class="mb-0"><?= e($emp['name']) ?></h5>
                <p class="text-muted mb-2"><?= e($emp['designation']) ?> <?= $emp['department'] ? '&middot; ' . e($emp['department']) : '' ?></p>
                <span class="badge <?= $emp['status']==='Active'?'bg-success':'bg-secondary' ?>"><?= e($emp['status']) ?></span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Code</span><span><?= e($emp['employee_code']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Mobile</span><span><?= e($emp['mobile'] ?: '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span><span><?= e($emp['email'] ?: '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Joined</span><span><?= e($emp['joining_date'] ?: '-') ?></span></li>
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-1" id="summaryCards"></div>
        <div class="card mt-2">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <span><i class="fa-solid fa-clock-rotate-left me-2"></i>Recent Attendance</span>
                <span class="text-muted small" id="summaryPeriod"></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle" id="historyTable">
                        <thead><tr><th>Date</th><th>Status</th><th>In</th><th>Out</th><th>Working</th><th>OT</th><th>Remarks</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>window.PROFILE_ID = <?= (int)$id ?>;</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
