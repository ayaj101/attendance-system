/* Daily attendance entry with live working-hours / overtime calculation. */
(function ($) {
    'use strict';

    const cfg = window.ATT_CONFIG;
    const STATUSES = ['Present', 'Absent', 'Half Day', 'Leave', 'Holiday', 'Weekend'];

    function toMinutes(t) {
        if (!t) return null;
        const [h, m] = t.split(':').map(Number);
        if (isNaN(h) || isNaN(m)) return null;
        return h * 60 + m;
    }
    function toHHMM(min) {
        if (min < 0) min = 0;
        return String(Math.floor(min / 60)).padStart(2, '0') + ':' + String(min % 60).padStart(2, '0');
    }

    /* Mirror of the PHP calculation so the UI updates instantly. */
    function calc(inT, outT, status) {
        if (status !== 'Present' && status !== 'Half Day') return { w: '00:00', o: '00:00' };
        let i = toMinutes(inT), o = toMinutes(outT);
        if (i === null || o === null) return { w: '00:00', o: '00:00' };
        if (o < i) o += 24 * 60;
        const worked = Math.max(0, (o - i) - cfg.breakTime);
        const duty = toMinutes(cfg.dutyHours) || 510;
        const ot = Math.max(0, worked - duty);
        return { w: toHHMM(worked), o: toHHMM(ot) };
    }

    function statusOptions(selected) {
        return STATUSES.map((s) =>
            `<option value="${s}" ${s === selected ? 'selected' : ''}>${s}</option>`).join('');
    }

    function rowHtml(r, i) {
        const status = r.status || 'Present';
        const inT = (r.in_time || '').slice(0, 5);
        const outT = (r.out_time || '').slice(0, 5);
        const c = calc(inT, outT, status);
        return `<tr data-eid="${r.employee_id}" data-name="${(r.name||'').toLowerCase()} ${(r.employee_code||'').toLowerCase()}">
            <td>${i + 1}</td>
            <td><div class="fw-semibold">${r.name}</div><small class="text-muted">${r.employee_code} · ${r.department || ''}</small></td>
            <td><select class="form-select form-select-sm att-status">${statusOptions(status)}</select></td>
            <td><input type="time" class="form-control form-control-sm att-in" value="${inT}"></td>
            <td><input type="time" class="form-control form-control-sm att-out" value="${outT}"></td>
            <td class="text-center att-working fw-semibold">${c.w}</td>
            <td class="text-center att-overtime fw-semibold text-primary">${c.o}</td>
            <td><input type="text" class="form-control form-control-sm att-remarks" value="${(r.remarks || '').replace(/"/g,'&quot;')}" placeholder="Remarks"></td>
        </tr>`;
    }

    function recalcRow($tr) {
        const status = $tr.find('.att-status').val();
        const inT = $tr.find('.att-in').val();
        const outT = $tr.find('.att-out').val();
        const timed = (status === 'Present' || status === 'Half Day');
        $tr.find('.att-in, .att-out').prop('disabled', !timed);
        const c = calc(inT, outT, status);
        $tr.find('.att-working').text(c.w);
        $tr.find('.att-overtime').text(c.o);
    }

    function load() {
        const date = $('#attDate').val();
        App.ajax('api/attendance/get.php', { data: { date } }).then((res) => {
            const body = $('#attendanceBody').empty();
            if (!res.data.length) {
                body.html('<tr><td colspan="8" class="text-center text-muted py-4">No active employees found.</td></tr>');
                return;
            }
            res.data.forEach((r, i) => body.append(rowHtml(r, i)));
            $('#attendanceBody tr').each(function () { recalcRow($(this)); });
        }).catch((e) => App.toast(e.message, 'error'));
    }

    function save() {
        const date = $('#attDate').val();
        const records = [];
        $('#attendanceBody tr[data-eid]').each(function () {
            const $t = $(this);
            records.push({
                employee_id: $t.data('eid'),
                status: $t.find('.att-status').val(),
                in_time: $t.find('.att-in').val(),
                out_time: $t.find('.att-out').val(),
                remarks: $t.find('.att-remarks').val(),
            });
        });
        if (!records.length) { App.toast('Nothing to save.', 'warning'); return; }
        App.loading(true);
        App.ajax('api/attendance/save.php', {
            method: 'POST',
            data: { date, records: JSON.stringify(records) },
        }).then((res) => {
            App.loading(false);
            App.toast(res.message, res.success ? 'success' : 'error');
        }).catch((e) => { App.loading(false); App.toast(e.message, 'error'); });
    }

    $(function () {
        load();
        $('#attDate').on('change', load);
        $('#btnSaveAttendance').on('click', save);
        $('#bulkStatus').on('change', function () {
            const v = $(this).val();
            if (!v) return;
            $('#attendanceBody tr[data-eid]:visible').each(function () {
                $(this).find('.att-status').val(v);
                recalcRow($(this));
            });
        });
        $('#attSearch').on('input', function () {
            const q = $(this).val().toLowerCase();
            $('#attendanceBody tr[data-eid]').each(function () {
                $(this).toggle($(this).data('name').indexOf(q) !== -1);
            });
        });
        $('#attendanceBody').on('change input', '.att-status, .att-in, .att-out', function () {
            recalcRow($(this).closest('tr'));
        });
    });
})(jQuery);
