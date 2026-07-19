<?php
/**
 * Reusable helper functions shared across pages and API endpoints.
 */

declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Escape a value for safe HTML output (XSS protection).
 */
function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Trim and normalise an incoming request value.
 */
function input(string $key, $default = null)
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

/**
 * Send a JSON response and stop execution.
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Build an absolute URL relative to the application base.
 */
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

/**
 * Cached application settings (single settings row).
 */
function settings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $stmt = db()->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
    $settings = $stmt->fetch() ?: [];
    return $settings;
}

/**
 * Convert a "HH:MM" or "HH:MM:SS" string to total minutes.
 */
function time_to_minutes(?string $time): ?int
{
    if (!$time) {
        return null;
    }
    $parts = explode(':', $time);
    if (count($parts) < 2) {
        return null;
    }
    return ((int) $parts[0]) * 60 + (int) $parts[1];
}

/**
 * Convert total minutes into a "HH:MM" string.
 */
function minutes_to_hhmm(int $minutes): string
{
    if ($minutes < 0) {
        $minutes = 0;
    }
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

/**
 * Calculate working hours and overtime for a shift.
 *
 * Returns ['working' => 'HH:MM', 'overtime' => 'HH:MM'] using the configured
 * break duration and duty-hours threshold. Handles overnight shifts.
 *
 * @return array{working:string, overtime:string, working_minutes:int, overtime_minutes:int}
 */
function calculate_hours(?string $inTime, ?string $outTime, string $status = 'Present'): array
{
    $empty = ['working' => '00:00', 'overtime' => '00:00', 'working_minutes' => 0, 'overtime_minutes' => 0];

    if (!in_array($status, ['Present', 'Half Day'], true)) {
        return $empty;
    }

    $in  = time_to_minutes($inTime);
    $out = time_to_minutes($outTime);
    if ($in === null || $out === null) {
        return $empty;
    }

    // Support overnight shifts where out-time is on the next day.
    if ($out < $in) {
        $out += 24 * 60;
    }

    $set        = settings();
    $breakMins  = (int) ($set['break_time'] ?? 30);
    $dutyMins   = time_to_minutes($set['duty_hours'] ?? '08:30') ?? 510;

    $worked = max(0, ($out - $in) - $breakMins);
    $overtime = max(0, $worked - $dutyMins);

    return [
        'working'          => minutes_to_hhmm($worked),
        'overtime'         => minutes_to_hhmm($overtime),
        'working_minutes'  => $worked,
        'overtime_minutes' => $overtime,
    ];
}

/**
 * Map an attendance status to a Bootstrap badge class.
 */
function status_badge_class(string $status): string
{
    return [
        'Present'  => 'bg-success',
        'Absent'   => 'bg-danger',
        'Half Day' => 'bg-warning text-dark',
        'Leave'    => 'bg-info text-dark',
        'Holiday'  => 'bg-secondary',
        'Weekend'  => 'bg-dark',
    ][$status] ?? 'bg-secondary';
}

/**
 * Record an entry in the activity/audit log.
 */
function log_activity(string $action, string $description = ''): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        // Logging must never break the main flow.
    }
}
