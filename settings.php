<?php
/**
 * Settings: office timing, company branding, theme, backup & restore.
 */
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle   = 'Settings';
$activePage  = 'settings';
$breadcrumbs = ['Settings' => null];
$pageScripts = ['assets/js/settings.js'];

$set = settings();
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-gear me-2"></i>General Settings</div>
            <div class="card-body">
                <form id="settingsForm" enctype="multipart/form-data">
                    <h6 class="text-muted mb-3">Office Timing</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Office Start</label>
                            <input type="time" class="form-control" name="office_start" value="<?= e(substr((string)$set['office_start'],0,5)) ?>">
                            <div class="invalid-feedback" data-field="office_start"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Office End</label>
                            <input type="time" class="form-control" name="office_end" value="<?= e(substr((string)$set['office_end'],0,5)) ?>">
                            <div class="invalid-feedback" data-field="office_end"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Break (min)</label>
                            <input type="number" class="form-control" name="break_time" min="0" max="240" value="<?= (int)$set['break_time'] ?>">
                            <div class="invalid-feedback" data-field="break_time"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Duty Hours</label>
                            <input type="text" class="form-control" name="duty_hours" placeholder="08:30" value="<?= e($set['duty_hours']) ?>">
                            <div class="invalid-feedback" data-field="duty_hours"></div>
                        </div>
                    </div>

                    <h6 class="text-muted mb-3">Company Branding</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" value="<?= e($set['company_name']) ?>">
                            <div class="invalid-feedback" data-field="company_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?= e($set['phone']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= e($set['email']) ?>">
                            <div class="invalid-feedback" data-field="email"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Theme</label>
                            <select class="form-select" name="theme">
                                <option value="light" <?= ($set['theme']??'')==='light'?'selected':'' ?>>Light</option>
                                <option value="dark" <?= ($set['theme']??'')==='dark'?'selected':'' ?>>Dark</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"><?= e($set['address']) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Logo</label>
                            <input type="file" class="form-control" name="company_logo" accept="image/*">
                        </div>
                        <?php if (!empty($set['company_logo'])): ?>
                        <div class="col-md-6 d-flex align-items-end">
                            <img src="<?= url($set['company_logo']) ?>" alt="logo" style="max-height:48px">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-database me-2"></i>Backup Database</div>
            <div class="card-body">
                <p class="text-muted small">Download a complete SQL backup (schema + data) of the system.</p>
                <a href="<?= url('api/settings/backup.php') ?>" class="btn btn-outline-primary">
                    <i class="fa-solid fa-download me-1"></i>Download Backup
                </a>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-rotate-left me-2"></i>Restore Database</div>
            <div class="card-body">
                <p class="text-muted small">Upload a previously downloaded .sql backup to restore data.</p>
                <form id="restoreForm" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="file" class="form-control" name="sql_file" accept=".sql" required>
                        <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-upload me-1"></i>Restore</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
