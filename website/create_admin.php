<?php
/**
 * create_admin.php - create an admin account (or promote an existing user).
 *
 * Run ONCE from the command line after importing schema.sql:
 *     php create_admin.php
 *
 * No default admin password is shipped with the project, so nobody can log in
 * with a password that is publicly known.
 */

if (PHP_SAPI !== 'cli') {
    exit('This script must be run from the command line.');
}

require_once __DIR__ . '/src/db.php';

function ask(string $question, bool $hidden = false): string
{
    echo $question;
    $canHide = $hidden && DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec');
    if ($canHide) {
        shell_exec('stty -echo');
    }
    $answer = trim((string) fgets(STDIN));
    if ($canHide) {
        shell_exec('stty echo');
        echo "\n";
    }
    return $answer;
}

echo "=== CloudCart: create an admin account ===\n";

$email = strtolower(ask('Admin email: '));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("That does not look like an email address.\n");
}

$existing = db_one('SELECT id, name FROM users WHERE email = ?', [$email]);
if ($existing) {
    db_exec('UPDATE users SET is_admin = 1 WHERE id = ?', [$existing['id']]);
    exit("User '{$existing['name']}' already existed and is now an admin.\n");
}

$name = ask('Admin name: ');
if ($name === '') {
    $name = 'Admin';
}
$password = ask('Password (min 8 characters; it may be visible while typing on Windows): ', true);
if (strlen($password) < 8) {
    exit("Password is too short.\n");
}

db_exec(
    'INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, 1)',
    [$name, $email, password_hash($password, PASSWORD_DEFAULT)]
);
echo "Admin '$email' created. You can log in now and open admin.php.\n";
