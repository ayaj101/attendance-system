/* Reports page: build filters, load data into a DataTable, and export. */
(function ($) {
    'use strict';

    let table;

    // Which filters are relevant for each report type.
    const filterMap = {
        daily:      ['date', 'dept', 'employee'],
        weekly:     ['range', 'dept', 'employee'],
        monthly:    ['month', 'dept', 'employee'],
        employee:   ['month', 'dept', 'employee'],
        department: ['month'],
        late:       ['month', 'dept', 'employee'],
        early:      ['month', 'dept', 'employee'],
        overtime:   ['month', 'dept', 'employee'],
        leave:      ['month', 'dept', 'employee'],
        summary:    ['month'],
    };

    function toggleFilters() {
        const type = $('#reportType').val();
        const active = filterMap[type] || [];
        $('.filter-date').toggle(active.includes('date'));
        $('.filter-range').toggle(active.includes('range'));
        $('.filter-month').toggle(active.includes('month'));
    }

    function collectParams() {
        return {
            type: $('#reportType').val(),
            date: $('#fDate').val(),
            from: $('#fFrom').val(),
            to: $('#fTo').val(),
            month: $('#fMonth').val(),
            year: $('#fYear').val(),
            department: $('#fDept').val(),
            employee: $('#fEmployee').val(),
        };
    }

    function generate() {
        App.loading(true);
        App.ajax('api/report/data.php', { data: collectParams() })
            .then((res) => {
                App.loading(false);
                $('#reportTitle').html('<i class="fa-solid fa-file-lines me-2"></i>' + res.title);
                if (table) { table.destroy(); }
                $('#reportTable thead').html('<tr>' + res.columns.map((c) => `<th>${c}</th>`).join('') + '</tr>');
                $('#reportTable tbody').empty();
                table = $('#reportTable').DataTable({
                    data: res.rows,
                    pageLength: 25,
                    language: { search: '_INPUT_', searchPlaceholder: 'Search...', emptyTable: 'No records found.' },
                });
            })
            .catch((e) => { App.loading(false); App.toast(e.message, 'error'); });
    }

    function exportReport(format) {
        const params = new URLSearchParams(collectParams());
        params.set('format', format === 'print' ? 'pdf' : format);
        const url = App.url('api/report/export.php?' + params.toString());
        window.open(url, '_blank');
    }

    $(function () {
        toggleFilters();
        $('#reportType').on('change', toggleFilters);
        $('#btnGenerate').on('click', generate);
        $('[data-export]').on('click', function () { exportReport($(this).data('export')); });

        // Global search prefill -> switch to employee-friendly daily report.
        if (window.REPORT_PREFILL) {
            $('#reportTable thead').html('<tr><th></th></tr>');
        }
        generate();
    });
})(jQuery);
