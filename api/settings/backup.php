<?php
/**
 * Download a full SQL backup of the database (schema + data), generated in pure
 * PHP so it works on any XAMPP install without mysqldump on the PATH.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_login();

$pdo = db();
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . DB_NAME . '_backup_' . date('Ymd_His') . '.sql"');

echo "-- Backup of `" . DB_NAME . "` generated " . date('Y-m-d H:i:s') . "\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM);
    echo "DROP TABLE IF EXISTS `{$table}`;\n";
    echo $create[1] . ";\n\n";

    $rows = $pdo->query('SELECT * FROM `' . $table . '`');
    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
        $cols = array_map(fn($c) => '`' . $c . '`', array_keys($row));
        $vals = array_map(function ($v) use ($pdo) {
            return $v === null ? 'NULL' : $pdo->quote((string) $v);
        }, array_values($row));
        echo "INSERT INTO `{$table}` (" . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
    }
    echo "\n";
}
echo "SET FOREIGN_KEY_CHECKS=1;\n";

log_activity('backup', 'Downloaded database backup.');
exit;
