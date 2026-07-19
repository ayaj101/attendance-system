<?php
/**
 * Authentication, session management and CSRF protection helpers.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Whether a user is currently authenticated.
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Return the currently authenticated user (or null).
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    $stmt = db()->prepare('SELECT id, username, name, role, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

/**
 * Attempt to log a user in with the given credentials.
 */
function attempt_login(string $username, string $password, bool $remember = false): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];

    if ($remember) {
        create_remember_token((int) $user['id']);
    }

    log_activity('login', 'User logged in: ' . $username);
    return true;
}

/**
 * Issue a persistent "remember me" cookie backed by a hashed DB token.
 */
function create_remember_token(int $userId): void
{
    $token = bin2hex(random_bytes(32));
    $stmt = db()->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
    $stmt->execute([hash('sha256', $token), $userId]);
    setcookie('remember_token', $userId . ':' . $token, [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Restore a session from a valid remember-me cookie, if present.
 */
function login_from_cookie(): void
{
    if (is_logged_in() || empty($_COOKIE['remember_token'])) {
        return;
    }
    [$userId, $token] = array_pad(explode(':', $_COOKIE['remember_token'], 2), 2, '');
    if (!ctype_digit($userId) || $token === '') {
        return;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND remember_token = ? LIMIT 1');
    $stmt->execute([$userId, hash('sha256', $token)]);
    if ($user = $stmt->fetch()) {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
    }
}

/**
 * Log the current user out and clear all session/cookie state.
 */
function logout(): void
{
    if (is_logged_in()) {
        log_activity('logout', 'User logged out: ' . ($_SESSION['username'] ?? ''));
        db()->prepare('UPDATE users SET remember_token = NULL WHERE id = ?')
            ->execute([$_SESSION['user_id']]);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    setcookie('remember_token', '', time() - 42000, '/');
    session_destroy();
}

/**
 * Guard a page: redirect to login when not authenticated.
 */
function require_login(): void
{
    login_from_cookie();
    if (!is_logged_in()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

/**
 * Guard an API endpoint: return 401 JSON when not authenticated.
 */
function require_api_login(): void
{
    login_from_cookie();
    if (!is_logged_in()) {
        json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
    }
}

/**
 * Return (creating if needed) the CSRF token for this session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validate the CSRF token from a request (header or body).
 */
function verify_csrf(): bool
{
    $token = $_POST['csrf_token']
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforce CSRF for API mutations, returning 419 JSON on failure.
 */
function require_csrf(): void
{
    if (!verify_csrf()) {
        json_response(['success' => false, 'message' => 'Invalid or missing CSRF token.'], 419);
    }
}
