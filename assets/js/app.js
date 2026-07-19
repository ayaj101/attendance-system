/* =============================================================
   Shared front-end utilities: toasts, confirm dialog, AJAX,
   sidebar + theme toggles, loading overlay.
   ============================================================= */
(function () {
    'use strict';

    const App = window.App = {};

    App.baseUrl = document.querySelector('meta[name="base-url"]')?.content || '/';
    App.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    App.url = (path) => App.baseUrl + String(path).replace(/^\//, '');

    /* ---------- Toast notifications ---------- */
    App.toast = function (message, type = 'success') {
        const colors = { success: 'text-bg-success', error: 'text-bg-danger', warning: 'text-bg-warning', info: 'text-bg-info' };
        const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
        const container = document.getElementById('toastContainer');
        if (!container) { alert(message); return; }
        const el = document.createElement('div');
        el.className = `toast align-items-center border-0 ${colors[type] || colors.info}`;
        el.setAttribute('role', 'alert');
        el.innerHTML = `<div class="d-flex">
            <div class="toast-body"><i class="fa-solid ${icons[type] || icons.info} me-2"></i>${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
        container.appendChild(el);
        const t = new bootstrap.Toast(el, { delay: 3500 });
        t.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    };

    /* ---------- Confirm dialog (returns a Promise) ---------- */
    App.confirm = function (message = 'Are you sure?', okLabel = 'Confirm') {
        return new Promise((resolve) => {
            const modalEl = document.getElementById('confirmModal');
            if (!modalEl) { resolve(window.confirm(message)); return; }
            document.getElementById('confirmModalBody').innerHTML = message;
            const okBtn = document.getElementById('confirmModalOk');
            okBtn.textContent = okLabel;
            const modal = new bootstrap.Modal(modalEl);
            const onOk = () => { cleanup(); modal.hide(); resolve(true); };
            const onHide = () => { cleanup(); resolve(false); };
            function cleanup() {
                okBtn.removeEventListener('click', onOk);
                modalEl.removeEventListener('hidden.bs.modal', onHide);
            }
            okBtn.addEventListener('click', onOk);
            modalEl.addEventListener('hidden.bs.modal', onHide, { once: true });
            modal.show();
        });
    };

    /* ---------- Loading overlay ---------- */
    App.loading = (show) => document.getElementById('loadingOverlay')?.classList.toggle('show', !!show);

    /* ---------- AJAX helper ---------- */
    App.ajax = async function (endpoint, { method = 'GET', data = null, isForm = false } = {}) {
        const opts = { method, headers: { 'X-CSRF-Token': App.csrfToken } };
        if (data) {
            if (isForm) {
                opts.body = data; // FormData
            } else if (method === 'GET') {
                const qs = new URLSearchParams(data).toString();
                endpoint += (endpoint.includes('?') ? '&' : '?') + qs;
            } else {
                opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                opts.body = new URLSearchParams(data).toString();
            }
        }
        const res = await fetch(App.url(endpoint), opts);
        const text = await res.text();
        let json;
        try { json = JSON.parse(text); }
        catch (e) { throw new Error('Unexpected server response: ' + text.slice(0, 200)); }
        if (!res.ok && json.message) throw new Error(json.message);
        return json;
    };

    /* ---------- Sidebar toggle (mobile) ---------- */
    function initSidebar() {
        const sidebar = document.getElementById('appSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const open = () => { sidebar?.classList.add('show'); backdrop?.classList.add('show'); };
        const close = () => { sidebar?.classList.remove('show'); backdrop?.classList.remove('show'); };
        document.getElementById('sidebarToggle')?.addEventListener('click', open);
        document.getElementById('sidebarClose')?.addEventListener('click', close);
        backdrop?.addEventListener('click', close);
    }

    /* ---------- Theme toggle ---------- */
    function initTheme() {
        const btn = document.getElementById('themeToggle');
        btn?.addEventListener('click', async () => {
            const html = document.documentElement;
            const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            btn.querySelector('i').className = 'fa-solid ' + (next === 'dark' ? 'fa-sun' : 'fa-moon');
            try {
                await App.ajax('api/settings/theme.php', { method: 'POST', data: { theme: next } });
            } catch (e) { /* non-critical */ }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initTheme();
    });
})();
