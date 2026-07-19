<?php
/**
 * One-click installer.
 *
 * Creates the database + tables from database/attendance.sql and loads demo
 * data. Visit http://localhost/attendance-system/install.php once after copying
 * the project into XAMPP's htdocs folder.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$messages = [];
$error = null;
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect without selecting a database so we can create it.
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT),
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $sql = file_get_contents(__DIR__ . '/database/attendance.sql');
        if ($sql === false) {
            throw new RuntimeException('Could not read database/attendance.sql');
        }
        $pdo->exec($sql);
        $messages[] = 'Database and tables created successfully.';

        // Seed demo data.
        require_once __DIR__ . '/database/seed.php';
        foreach (seed_database() as $line) {
            $messages[] = $line;
        }
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install &middot; <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 640px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h3 class="mb-3"><i class="fa-solid fa-database text-primary me-2"></i>Setup Wizard</h3>
            <p class="text-muted">This will create the <code>attendance_system</code> database, all tables, a
                default admin user and demo data (30 employees + one month of attendance).</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><strong>Error:</strong> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php foreach ($messages as $m): ?>
                <div class="alert alert-success py-2 mb-2"><i class="fa-solid fa-check me-2"></i><?= htmlspecialchars($m) ?></div>
            <?php endforeach; ?>

            <?php if ($done): ?>
                <div class="alert alert-info">
                    Installation complete. Login with <strong>admin</strong> / <strong>admin123</strong>.
                </div>
                <a href="<?= BASE_URL ?>login.php" class="btn btn-primary w-100">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Go to Login
                </a>
            <?php else: ?>
                <form method="post">
                    <div class="alert alert-warning py-2">Running this will reset existing demo data.</div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-play me-2"></i>Run Installation
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <p class="text-center text-muted mt-3 small"><?= APP_NAME ?></p>
</div>
</body>
</html>
