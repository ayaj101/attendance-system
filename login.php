<?php
/**
 * Login page.
 */
require_once __DIR__ . '/config/auth.php';

login_from_cookie();
if (is_logged_in()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = input('username', '');
        $password = (string) input('password', '');
        $remember = !empty($_POST['remember']);
        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } elseif (attempt_login($username, $password, $remember)) {
            header('Location: ' . url('dashboard.php'));
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
$set = settings();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &middot; <?= e($set['company_name'] ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="login-page">
    <div class="card login-card shadow-lg">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <span class="login-logo mb-3"><i class="fa-solid fa-fingerprint"></i></span>
                <h4 class="fw-bold mb-1"><?= e($set['company_name'] ?? 'Attendance System') ?></h4>
                <p class="text-muted mb-0">Attendance &amp; Overtime Management</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePw" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Login
                </button>
            </form>

            <div class="alert alert-info mt-4 mb-0 py-2 small">
                <strong>Demo credentials:</strong> admin / admin123
            </div>
        </div>
    </div>
    <script>
        document.getElementById('togglePw').addEventListener('click', function () {
            const pw = document.getElementById('password');
            const isText = pw.type === 'text';
            pw.type = isText ? 'password' : 'text';
            this.querySelector('i').className = 'fa-solid ' + (isText ? 'fa-eye' : 'fa-eye-slash');
        });
    </script>
</body>
</html>
