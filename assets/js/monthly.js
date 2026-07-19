/* Monthly attendance sheet renderer. */
(function ($) {
    'use strict';

    let current = { year: 0, month: 0 };

    function load() {
        const year = $('#selYear').val();
        const month = $('#selMonth').val();
        const department = $('#selDept').val();
        current = { year, month, department };
        App.ajax('api/attendance/monthly.php', { data: { year, month, department } })
            .then(render)
            .catch((e) => App.toast(e.message, 'error'));
    }

    function render(res) {
        $('#sheetTitle').text(res.month_name);
        const days = res.days_in_month;

        let head = '<th class="emp-name">Employee</th>';
        for (let d = 1; d <= days; d++) {
            const dow = new Date(res.year, res.month - 1, d).getDay(); // 0=Sun
            head += `<th class="${dow === 0 ? 'text-danger' : ''}">${d}</th>`;
        }
        head += '<th>P</th><th>A</th><th>H</th><th>L</th><th>Hours</th><th>OT</th>';
        $('#sheetHead').html(head);

        const body = $('#sheetBody').empty();
        if (!res.rows.length) {
            body.html(`<tr><td class="text-center text-muted py-4" colspan="${days + 7}">No employees found.</td></tr>`);
            return;
        }
        res.rows.forEach((r) => {
            let row = `<td class="emp-name"><div class="fw-semibold">${r.name}</div><small class="text-muted">${r.code}</small></td>`;
            for (let d = 1; d <= days; d++) {
                const code = r.days[d] || '';
                const cls = code ? 'cell-' + code : '';
                const clickable = ['P', 'A', 'H', 'L'].includes(code);
                row += `<td class="${cls}" ${clickable ? `data-date="${res.year}-${String(res.month).padStart(2,'0')}-${String(d).padStart(2,'0')}"` : ''}>${code}</td>`;
            }
            row += `<td class="fw-semibold text-success">${r.present}</td>
                    <td class="fw-semibold text-danger">${r.absent}</td>
                    <td class="fw-semibold text-warning">${r.halfday}</td>
                    <td class="fw-semibold text-info">${r.leave}</td>
                    <td>${r.total_hours}</td>
                    <td class="text-primary">${r.total_ot}</td>`;
            body.append(`<tr>${row}</tr>`);
        });
    }

    function exportExcel() {
        const params = new URLSearchParams({ view: 'sheet', format: 'excel', ...current });
        window.location = App.url('api/report/export.php?' + params.toString());
    }

    $(function () {
        load();
        $('#selMonth, #selYear, #selDept').on('change', load);
        $('#btnPrintMonthly').on('click', () => window.print());
        $('#btnExportMonthly').on('click', exportExcel);
        // Click a colored day cell -> jump to that day's attendance page.
        $('#sheetBody').on('click', 'td[data-date]', function () {
            window.location = App.url('attendance.php?date=' + $(this).data('date'));
        });
    });
})(jQuery);
