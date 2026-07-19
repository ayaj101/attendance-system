<?php
/**
 * Responsive sidebar navigation. Expects $active to hold the current page key.
 */
$active = $activePage ?? '';
$nav = [
    'dashboard'  => ['dashboard.php', 'Dashboard', 'fa-gauge-high'],
    'attendance' => ['attendance.php', 'Attendance', 'fa-clipboard-check'],
    'monthly'    => ['monthly.php', 'Monthly Sheet', 'fa-table-cells'],
    'employees'  => ['employees.php', 'Employees', 'fa-users'],
    'departments'=> ['departments.php', 'Departments', 'fa-sitemap'],
    'reports'    => ['reports.php', 'Reports', 'fa-chart-line'],
    'settings'   => ['settings.php', 'Settings', 'fa-gear'],
];
$set = settings();
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <a href="<?= url('dashboard.php') ?>" class="d-flex align-items-center text-decoration-none">
            <span class="brand-icon"><i class="fa-solid fa-fingerprint"></i></span>
            <span class="brand-text"><?= e($set['company_name'] ?? 'Attendance') ?></span>
        </a>
        <button class="btn btn-sm sidebar-close d-lg-none" id="sidebarClose" aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <?php foreach ($nav as $key => [$link, $label, $icon]): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= url($link) ?>">
                        <i class="fa-solid <?= $icon ?> fa-fw me-2"></i><span><?= $label ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar-footer small text-center">
        <span class="text-muted">v1.0 &middot; <?= date('Y') ?></span>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
