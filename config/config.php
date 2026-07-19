<?php
/**
 * Global application configuration.
 *
 * Central place for app-wide constants. Adjust BASE_URL only if you install the
 * project in a sub-folder other than /attendance-system on XAMPP.
 */

declare(strict_types=1);

// Show errors during development. Set display_errors to 0 in production.
error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Asia/Kolkata');

// --- Database credentials (defaults match a standard XAMPP install) ---
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'attendance_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// --- Application meta ---
define('APP_NAME', 'Attendance & Overtime Management System');

/**
 * Resolve the base URL of the application automatically so the project works
 * whether it is served from http://localhost/attendance-system or a vhost root.
 */
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    // Normalise paths coming from files inside sub-directories (api/, config/).
    foreach (['/api/attendance', '/api/employee', '/api/report', '/api/auth',
              '/api/department', '/api/settings', '/api', '/config', '/includes'] as $sub) {
        if (str_ends_with($scriptDir, $sub)) {
            $scriptDir = substr($scriptDir, 0, -strlen($sub));
            break;
        }
    }
    $scriptDir = rtrim($scriptDir, '/');
    define('BASE_URL', $scriptDir === '' ? '/' : $scriptDir . '/');
}

// Absolute filesystem paths.
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
