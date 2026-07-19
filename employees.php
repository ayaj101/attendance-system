<?php
/**
 * Employee management: list, add, edit, view, delete (AJAX + DataTables).
 */
require_once __DIR__ . '/config/auth.php';
require_login();

$pageTitle   = 'Employees';
$activePage  = 'employees';
$breadcrumbs = ['Employees' => null];
$pageScripts = ['assets/js/employees.js'];

$departments = db()->query('SELECT name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span><i class="fa-solid fa-users me-2"></i>Employee List</span>
        <button class="btn btn-primary btn-sm" id="btnAddEmployee">
            <i class="fa-solid fa-user-plus me-1"></i> Add Employee
        </button>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-sm-4 col-md-3">
                <select class="form-select form-select-sm" id="filterDepartment">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= e($d) ?>"><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 col-md-3">
                <select class="form-select form-select-sm" id="filterStatus">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="employeesTable" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="employeeForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="employeeModalTitle">Add Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="emp_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee Code *</label>
                            <input type="text" class="form-control" name="employee_code" id="emp_code" required>
                            <div class="invalid-feedback" data-field="employee_code"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" id="emp_name" required>
                            <div class="invalid-feedback" data-field="name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department" id="emp_department">
                                <option value="">Select...</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= e($d) ?>"><?= e($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" name="designation" id="emp_designation">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" name="mobile" id="emp_mobile">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="emp_email">
                            <div class="invalid-feedback" data-field="email"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Joining Date</label>
                            <input type="date" class="form-control" name="joining_date" id="emp_joining_date">
                            <div class="invalid-feedback" data-field="joining_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="emp_status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Photo (optional)</label>
                            <input type="file" class="form-control" name="photo" id="emp_photo" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View modal -->
<div class="modal fade" id="employeeViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="employeeViewBody"></div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-primary" id="viewProfileLink"><i class="fa-solid fa-id-card me-1"></i>Full Profile</a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
