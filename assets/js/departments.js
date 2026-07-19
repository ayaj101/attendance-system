/* Department CRUD. */
(function ($) {
    'use strict';

    let table;
    const modal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('deptModal'));

    function load() {
        App.ajax('api/department/list.php').then((res) => {
            const rows = res.data.map((d, i) => [
                i + 1,
                d.name,
                `<span class="badge bg-secondary">${d.employee_count}</span>`,
                `<div class="text-end text-nowrap">
                    <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${d.id}" data-name="${d.name}"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-sm btn-outline-danger btn-del" data-id="${d.id}"><i class="fa-solid fa-trash"></i></button>
                </div>`,
            ]);
            if (table) { table.clear().rows.add(rows).draw(); }
            else {
                table = $('#deptTable').DataTable({
                    data: rows, order: [[1, 'asc']],
                    columnDefs: [{ orderable: false, targets: [3] }],
                });
            }
        }).catch((e) => App.toast(e.message, 'error'));
    }

    $(function () {
        load();
        $('#btnAddDept').on('click', function () {
            $('#deptForm')[0].reset();
            $('#dept_id').val('');
            $('#deptModalTitle').text('Add Department');
            $('#dept_name').removeClass('is-invalid');
            modal().show();
        });
        $('#deptTable').on('click', '.btn-edit', function () {
            $('#dept_id').val($(this).data('id'));
            $('#dept_name').val($(this).data('name')).removeClass('is-invalid');
            $('#deptModalTitle').text('Edit Department');
            modal().show();
        });
        $('#deptTable').on('click', '.btn-del', function () {
            const id = $(this).data('id');
            App.confirm('Delete this department?', 'Delete').then((ok) => {
                if (!ok) return;
                App.ajax('api/department/delete.php', { method: 'POST', data: { id } })
                    .then((res) => { App.toast(res.message, 'success'); load(); })
                    .catch((e) => App.toast(e.message, 'error'));
            });
        });
        $('#deptForm').on('submit', function (ev) {
            ev.preventDefault();
            const data = { id: $('#dept_id').val(), name: $('#dept_name').val() };
            App.ajax('api/department/save.php', { method: 'POST', data })
                .then((res) => {
                    if (res.success) { modal().hide(); App.toast(res.message, 'success'); load(); }
                    else { $('#dept_name').addClass('is-invalid'); App.toast(res.message, 'error'); }
                })
                .catch((e) => App.toast(e.message, 'error'));
        });
    });
})(jQuery);
