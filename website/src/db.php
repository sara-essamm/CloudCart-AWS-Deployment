<?php
/**
 * db.php - MySQL connection (PDO) and small helper functions.
 *
 * All queries use prepared statements (the "?" placeholders), which protects
 * the site against SQL injection.
 */

require_once __DIR__ . '/config.php';

/** Returns the one shared database connection (created on first use). */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // DB_DSN is an optional override (used for automated testing).
        $dsn = env('DB_DSN');
        if (!$dsn) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', 'localhost'),
                env('DB_PORT', '3306'),
                env('DB_NAME', 'cloudcart')
            );
        }

        $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errors become exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows as arrays
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10,
        ]);

        if (strpos($dsn, 'sqlite') === 0) {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    return $pdo;
}

/** SELECT: returns all rows. */
function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** SELECT: returns the first row, or null. */
function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** INSERT / UPDATE / DELETE: returns the number of affected rows. */
function db_exec(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/** INSERT: returns the id of the new row. */
function db_insert(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) db()->lastInsertId();
}

/** True if the database answers a simple query (used by health.php?db=1). */
function db_check(): bool
{
    try {
        db_one('SELECT 1 AS ok');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
