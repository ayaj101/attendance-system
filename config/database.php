<?php
/**
 * Database connection using PDO with prepared statements.
 *
 * Exposes a singleton PDO instance via db() to avoid multiple connections.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Return a shared PDO connection.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Fail loudly during setup so the developer knows to import the schema.
        http_response_code(500);
        die(
            'Database connection failed: ' . htmlspecialchars($e->getMessage()) .
            '. Make sure MySQL is running and that you imported database/attendance.sql.'
        );
    }

    return $pdo;
}
