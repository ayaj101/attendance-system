<?php
/**
 * Dashboard: summary cards, charts, quick actions and recent activity.
 */
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle   = 'Dashboard';
$activePage  = 'dashboard';
$breadcrumbs = ['Dashboard' => null];
$pageScripts = ['assets/js/dashboard.js'];

include __DIR__ . '/includes/header.php';

$cards = [
    ['id' => 'total_employees', 'label' => 'Total Employees', 'icon' => 'fa-users', 'grad' => 'bg-grad-primary'],
    ['id' => 'present_today',   'label' => 'Present Today',   'icon' => 'fa-user-check', 'grad' => 'bg-grad-success'],
    ['id' => 'absent_today',    'label' => 'Absent Today',    'icon' => 'fa-user-xmark', 'grad' => 'bg-grad-danger'],
    ['id' => 'halfday_today',   'label' => 'Half Day',        'icon' => 'fa-clock', 'grad' => 'bg-grad-warning'],
    ['id' => 'leave_today',     'label' => 'On Leave',        'icon' => 'fa-plane-departure', 'grad' => 'bg-grad-info'],
    ['id' => 'overtime_today',  'label' => 'Overtime Today',  'icon' => 'fa-business-time', 'grad' => 'bg-grad-purple'],
];
?>
<div class="row g-3 mb-1">
    <?php foreach ($cards as $c): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon <?= $c['grad'] ?>"><i class="fa-solid <?= $c['icon'] ?>"></i></span>
                    <div>
                        <div class="stat-value" data-card="<?= $c['id'] ?>">0</div>
                        <div class="stat-label"><?= $c['label'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-1">
    <div class="col-md-3 col-6">
        <div class="card"><div class="card-body text-center">
            <div class="stat-value text-primary" data-card="monthly_pct">0</div>
            <div class="stat-label">Monthly Attendance %</div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card"><div class="card-body text-center">
            <div class="stat-value text-success" data-card="avg_working">0</div>
            <div class="stat-label">Avg Working Hrs</div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card"><div class="card-body text-center">
            <div class="stat-value text-purple" data-card="monthly_ot_hours">0</div>
            <div class="stat-label">Monthly OT (hrs)</div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card"><div class="card-body text-center">
            <div class="stat-value text-info" data-card="departments">0</div>
            <div class="stat-label">Departments</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-chart-line me-2"></i>Daily Attendance Trend <small class="text-muted" id="chartMonth"></small></span>
            </div>
            <div class="card-body"><canvas id="trendChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="fa-solid fa-chart-pie me-2"></i>Monthly Breakdown</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="breakdownChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="fa-solid fa-business-time me-2"></i>Overtime Trend</div>
            <div class="card-body"><canvas id="overtimeChart" height="90"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="fa-solid fa-clock-rotate-left me-2"></i>Recent Activity</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="recentActivity">
                    <li class="list-group-item text-muted text-center py-4">Loading...</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
