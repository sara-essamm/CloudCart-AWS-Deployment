<?php
/**
 * DbSessionHandler - stores PHP sessions in the MySQL "sessions" table.
 *
 * Why? By default PHP keeps sessions in files on ONE server. With two EC2
 * instances behind a load balancer, a user logged in on server A would look
 * logged out on server B. Keeping sessions in the shared database fixes that,
 * so no "sticky sessions" are needed on the load balancer.
 */

require_once __DIR__ . '/db.php';

class DbSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $row = db_one('SELECT data FROM sessions WHERE id = ?', [$id]);
        return $row ? (string) $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        db_exec('REPLACE INTO sessions (id, data, last_access) VALUES (?, ?, ?)',
                [$id, $data, time()]);
        return true;
    }

    public function destroy(string $id): bool
    {
        db_exec('DELETE FROM sessions WHERE id = ?', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return db_exec('DELETE FROM sessions WHERE last_access < ?', [time() - $max_lifetime]);
    }
}
