/* Dashboard: load stats + render Chart.js charts + recent activity. */
(function ($) {
    'use strict';

    function timeAgo(dateStr) {
        const d = new Date(dateStr.replace(' ', 'T'));
        const s = Math.floor((Date.now() - d.getTime()) / 1000);
        if (s < 60) return 'just now';
        if (s < 3600) return Math.floor(s / 60) + 'm ago';
        if (s < 86400) return Math.floor(s / 3600) + 'h ago';
        return Math.floor(s / 86400) + 'd ago';
    }

    const actionIcon = {
        login: 'fa-right-to-bracket', logout: 'fa-right-from-bracket',
        employee_create: 'fa-user-plus', employee_update: 'fa-user-pen',
        employee_delete: 'fa-user-minus', attendance_save: 'fa-clipboard-check',
        settings_update: 'fa-gear',
    };

    function render(res) {
        Object.entries(res.cards).forEach(([k, v]) => {
            const el = document.querySelector(`[data-card="${k}"]`);
            if (!el) return;
            if (k === 'monthly_pct') el.textContent = v + '%';
            else if (k === 'avg_working') el.textContent = v + 'h';
            else el.textContent = v;
        });
        $('#chartMonth').text('· ' + res.month);

        const gridColor = getComputedStyle(document.body).getPropertyValue('--bs-border-color') || '#ddd';

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: res.charts.trend.labels,
                datasets: [{
                    label: 'Present', data: res.charts.trend.data,
                    borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.12)',
                    fill: true, tension: .35, pointRadius: 2,
                }],
            },
            options: { responsive: true, plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { display: false } } } },
        });

        new Chart(document.getElementById('overtimeChart'), {
            type: 'bar',
            data: {
                labels: res.charts.overtime.labels,
                datasets: [{ label: 'OT (hrs)', data: res.charts.overtime.data, backgroundColor: '#a855f7' }],
            },
            options: { responsive: true, plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { color: gridColor } }, x: { grid: { display: false } } } },
        });

        new Chart(document.getElementById('breakdownChart'), {
            type: 'doughnut',
            data: {
                labels: res.charts.breakdown.labels,
                datasets: [{
                    data: res.charts.breakdown.data,
                    backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#0ea5e9', '#64748b', '#1e293b'],
                }],
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } },
        });

        const list = $('#recentActivity').empty();
        if (!res.recent.length) {
            list.html('<li class="list-group-item text-muted text-center py-4">No recent activity.</li>');
        } else {
            res.recent.forEach((a) => {
                const icon = actionIcon[a.action] || 'fa-circle-info';
                list.append(`<li class="list-group-item d-flex align-items-start gap-2">
                    <i class="fa-solid ${icon} text-primary mt-1"></i>
                    <div class="flex-grow-1">
                        <div class="small">${a.description || a.action}</div>
                        <small class="text-muted">${a.name || 'System'} · ${timeAgo(a.created_at)}</small>
                    </div></li>`);
            });
        }
    }

    $(function () {
        App.ajax('api/report/dashboard.php')
            .then(render)
            .catch((e) => App.toast(e.message, 'error'));
    });
})(jQuery);
