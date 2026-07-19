/* Employee profile: load summaries + attendance history. */
(function ($) {
    'use strict';

    const badgeClass = {
        Present: 'bg-success', Absent: 'bg-danger', 'Half Day': 'bg-warning text-dark',
        Leave: 'bg-info text-dark', Holiday: 'bg-secondary', Weekend: 'bg-dark',
    };

    function card(label, value, icon, cls) {
        return `<div class="col-6 col-md-3">
            <div class="card stat-card h-100"><div class="card-body text-center">
                <span class="stat-icon ${cls} mb-2"><i class="fa-solid ${icon}"></i></span>
                <div class="stat-value mt-2">${value}</div>
                <div class="stat-label">${label}</div>
            </div></div></div>`;
    }

    function render(res) {
        const m = res.monthly;
        $('#summaryPeriod').text(res.month_name + ' (monthly) · ' + res.year + ' (yearly OT: ' + res.yearly.overtime + ')');
        $('#summaryCards').html([
            card('Present', m.present, 'fa-user-check', 'bg-grad-success'),
            card('Absent', m.absent, 'fa-user-xmark', 'bg-grad-danger'),
            card('Leaves', m.leave, 'fa-plane-departure', 'bg-grad-info'),
            card('Half Days', m.halfday, 'fa-clock', 'bg-grad-warning'),
            card('Working Hrs', m.working_hours, 'fa-business-time', 'bg-grad-primary'),
            card('Overtime', m.overtime, 'fa-hourglass-half', 'bg-grad-purple'),
            card('Late Arrivals', m.late, 'fa-person-running', 'bg-grad-danger'),
            card('Early Exits', m.early, 'fa-door-open', 'bg-grad-warning'),
        ].join(''));

        const body = $('#historyTable tbody').empty();
        if (!res.history.length) {
            body.html('<tr><td colspan="7" class="text-center text-muted py-3">No attendance records.</td></tr>');
            return;
        }
        res.history.forEach((h) => {
            body.append(`<tr>
                <td>${h.attendance_date}</td>
                <td><span class="badge ${badgeClass[h.status] || 'bg-secondary'}">${h.status}</span></td>
                <td>${(h.in_time || '-').slice(0,5)}</td>
                <td>${(h.out_time || '-').slice(0,5)}</td>
                <td>${h.working_hours || '00:00'}</td>
                <td class="text-primary">${h.overtime || '00:00'}</td>
                <td>${h.remarks || ''}</td>
            </tr>`);
        });
    }

    $(function () {
        App.ajax('api/employee/profile.php', { data: { id: window.PROFILE_ID } })
            .then(render)
            .catch((e) => App.toast(e.message, 'error'));
    });
})(jQuery);
