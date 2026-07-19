/* Settings page: save settings + restore database via AJAX. */
(function ($) {
    'use strict';

    function clearErrors() { $('#settingsForm .is-invalid').removeClass('is-invalid'); }

    function showErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach((f) => {
            $(`#settingsForm [name="${f}"]`).addClass('is-invalid');
            $(`#settingsForm .invalid-feedback[data-field="${f}"]`).text(errors[f]);
        });
    }

    $(function () {
        $('#settingsForm').on('submit', function (ev) {
            ev.preventDefault();
            clearErrors();
            App.loading(true);
            App.ajax('api/settings/save.php', { method: 'POST', data: new FormData(this), isForm: true })
                .then((res) => {
                    App.loading(false);
                    if (res.success) {
                        App.toast(res.message, 'success');
                        setTimeout(() => location.reload(), 900);
                    } else {
                        showErrors(res.errors);
                        App.toast(res.message, 'error');
                    }
                })
                .catch((e) => { App.loading(false); App.toast(e.message, 'error'); });
        });

        $('#restoreForm').on('submit', function (ev) {
            ev.preventDefault();
            App.confirm('Restoring will overwrite current data. Continue?', 'Restore').then((ok) => {
                if (!ok) return;
                App.loading(true);
                App.ajax('api/settings/restore.php', { method: 'POST', data: new FormData(this), isForm: true })
                    .then((res) => {
                        App.loading(false);
                        App.toast(res.message, res.success ? 'success' : 'error');
                    })
                    .catch((e) => { App.loading(false); App.toast(e.message, 'error'); });
            });
        });
    });
})(jQuery);
