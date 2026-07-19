<?php
/**
 * Top navigation bar: sidebar toggle, global search, theme switch, user menu.
 */
$user = current_user();
$set  = settings();
$theme = $set['theme'] ?? 'light';
?>
<header class="app-navbar">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-icon d-lg-none" id="sidebarToggle" aria-label="Toggle menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <form class="global-search d-none d-md-block" action="<?= url('reports.php') ?>" method="get">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                </span>
                <input type="search" name="q" class="form-control border-start-0"
                       placeholder="Search employees, code, department...">
            </div>
        </form>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-icon" id="themeToggle" title="Toggle theme"
                data-theme="<?= e($theme) ?>">
            <i class="fa-solid <?= $theme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
        </button>

        <div class="dropdown">
            <button class="btn btn-icon position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="Quick actions">
                <i class="fa-solid fa-bolt"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Quick Actions</h6></li>
                <li><a class="dropdown-item" href="<?= url('employees.php?action=add') ?>"><i class="fa-solid fa-user-plus fa-fw me-2"></i>Add Employee</a></li>
                <li><a class="dropdown-item" href="<?= url('attendance.php') ?>"><i class="fa-solid fa-clipboard-check fa-fw me-2"></i>Mark Attendance</a></li>
                <li><a class="dropdown-item" href="<?= url('reports.php') ?>"><i class="fa-solid fa-file-lines fa-fw me-2"></i>View Reports</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <button class="btn user-menu d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar"><?= e(strtoupper(substr($user['name'] ?? 'A', 0, 1))) ?></span>
                <span class="d-none d-sm-inline text-start">
                    <span class="d-block fw-semibold lh-1"><?= e($user['name'] ?? 'Admin') ?></span>
                    <small class="text-muted text-capitalize"><?= e($user['role'] ?? 'admin') ?></small>
                </span>
                <i class="fa-solid fa-chevron-down small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= url('settings.php') ?>"><i class="fa-solid fa-gear fa-fw me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="fa-solid fa-right-from-bracket fa-fw me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>
