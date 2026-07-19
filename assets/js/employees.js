/* Employee management page logic (DataTables + AJAX CRUD). */
(function ($) {
    'use strict';

    let table;
    const modal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('employeeModal'));
    const viewModal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('employeeViewModal'));

    function badge(status) {
        return status === 'Active'
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';
    }

    function avatar(row) {
        if (row.photo) {
            return `<img src="${App.url(row.photo)}" class="avatar-sm me-2" alt="">`;
        }
        const initial = (row.name || '?').charAt(0).toUpperCase();
        return `<span class="avatar avatar-sm me-2 d-inline-flex align-items-center justify-content-center" style="font-size:.8rem">${initial}</span>`;
    }

    function loadTable() {
        const department = $('#filterDepartment').val();
        const status = $('#filterStatus').val();
        App.ajax('api/employee/list.php', { data: { department, status } }).then((res) => {
            const rows = res.data.map((r, i) => [
                i + 1,
                r.employee_code,
                `<div class="d-flex align-items-center">${avatar(r)}<div><div class="fw-semibold">${r.name}</div><small class="text-muted">${r.email || ''}</small></div></div>`,
                r.department || '-',
                r.designation || '-',
                r.mobile || '-',
                badge(r.status),
                actionButtons(r.id),
            ]);
            if (table) {
                table.clear().rows.add(rows).draw();
            } else {
                table = $('#employeesTable').DataTable({
                    data: rows,
                    order: [[0, 'asc']],
                    columnDefs: [{ orderable: false, targets: [2, 7] }],
                    language: { search: '_INPUT_', searchPlaceholder: 'Search...' },
                });
            }
        }).catch((e) => App.toast(e.message, 'error'));
    }

    function actionButtons(id) {
        return `<div class="text-end text-nowrap">
            <button class="btn btn-sm btn-outline-secondary btn-view" data-id="${id}" title="View"><i class="fa-solid fa-eye"></i></button>
            <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${id}" title="Edit"><i class="fa-solid fa-pen"></i></button>
            <button class="btn btn-sm btn-outline-danger btn-del" data-id="${id}" title="Delete"><i class="fa-solid fa-trash"></i></button>
        </div>`;
    }

    function openAdd() {
        document.getElementById('employeeForm').reset();
        $('#emp_id').val('');
        $('#employeeModalTitle').text('Add Employee');
        clearErrors();
        modal().show();
    }

    function openEdit(id) {
        App.ajax('api/employee/get.php', { data: { id } }).then((res) => {
            const e = res.data;
            clearErrors();
            $('#employeeModalTitle').text('Edit Employee');
            $('#emp_id').val(e.id);
            $('#emp_code').val(e.employee_code);
            $('#emp_name').val(e.name);
            $('#emp_department').val(e.department || '');
            $('#emp_designation').val(e.designation || '');
            $('#emp_mobile').val(e.mobile || '');
            $('#emp_email').val(e.email || '');
            $('#emp_joining_date').val(e.joining_date || '');
            $('#emp_status').val(e.status);
            modal().show();
        }).catch((e) => App.toast(e.message, 'error'));
    }

    function openView(id) {
        App.ajax('api/employee/get.php', { data: { id } }).then((res) => {
            const e = res.data;
            const photo = e.photo
                ? `<img src="${App.url(e.photo)}" class="rounded mb-3" style="width:90px;height:90px;object-fit:cover">`
                : `<span class="avatar mb-3" style="width:90px;height:90px;font-size:2rem">${(e.name||'?').charAt(0)}</span>`;
            $('#employeeViewBody').html(`
                <div class="text-center">${photo}<h5 class="mb-0">${e.name}</h5>
                <p class="text-muted">${e.designation || ''} ${e.department ? '&middot; ' + e.department : ''}</p></div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Code</span><span>${e.employee_code}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Mobile</span><span>${e.mobile || '-'}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span><span>${e.email || '-'}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Joining Date</span><span>${e.joining_date || '-'}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Status</span><span>${badge(e.status)}</span></li>
                </ul>`);
            $('#viewProfileLink').attr('href', App.url('profile.php?id=' + e.id));
            viewModal().show();
        }).catch((e) => App.toast(e.message, 'error'));
    }

    function clearErrors() {
        $('#employeeForm .is-invalid').removeClass('is-invalid');
    }

    function showErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach((field) => {
            const input = $(`#employeeForm [name="${field}"]`);
            input.addClass('is-invalid');
            $(`#employeeForm .invalid-feedback[data-field="${field}"]`).text(errors[field]);
        });
    }

    function submitForm(ev) {
        ev.preventDefault();
        clearErrors();
        const fd = new FormData(ev.target);
        App.loading(true);
        App.ajax('api/employee/save.php', { method: 'POST', data: fd, isForm: true })
            .then((res) => {
                App.loading(false);
                if (res.success) {
                    modal().hide();
                    App.toast(res.message, 'success');
                    loadTable();
                } else {
                    showErrors(res.errors);
                    App.toast(res.message || 'Validation failed', 'error');
                }
            })
            .catch((e) => { App.loading(false); App.toast(e.message, 'error'); });
    }

    function deleteEmployee(id) {
        App.confirm('Delete this employee and all their attendance records? This cannot be undone.', 'Delete')
            .then((ok) => {
                if (!ok) return;
                App.ajax('api/employee/delete.php', { method: 'POST', data: { id } })
                    .then((res) => { App.toast(res.message, 'success'); loadTable(); })
                    .catch((e) => App.toast(e.message, 'error'));
            });
    }

    $(function () {
        loadTable();
        $('#btnAddEmployee').on('click', openAdd);
        $('#filterDepartment, #filterStatus').on('change', loadTable);
        $('#employeeForm').on('submit', submitForm);
        $('#employeesTable').on('click', '.btn-edit', function () { openEdit($(this).data('id')); });
        $('#employeesTable').on('click', '.btn-view', function () { openView($(this).data('id')); });
        $('#employeesTable').on('click', '.btn-del', function () { deleteEmployee($(this).data('id')); });

        // Auto-open add modal via ?action=add
        if (new URLSearchParams(location.search).get('action') === 'add') openAdd();
    });
})(jQuery);
