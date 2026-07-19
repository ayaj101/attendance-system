<?php
/**
 * Department management (CRUD).
 */
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle   = 'Departments';
$activePage  = 'departments';
$breadcrumbs = ['Departments' => null];
$pageScripts = ['assets/js/departments.js'];

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-sitemap me-2"></i>Departments</span>
        <button class="btn btn-primary btn-sm" id="btnAddDept"><i class="fa-solid fa-plus me-1"></i>Add Department</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="deptTable" style="width:100%">
                <thead><tr><th>#</th><th>Department</th><th>Employees</th><th class="text-end">Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="deptForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="deptModalTitle">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="dept_id">
                    <label class="form-label">Department Name</label>
                    <input type="text" class="form-control" name="name" id="dept_name" required>
                    <div class="invalid-feedback" data-field="name"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
