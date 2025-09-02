<?php
// scripts/reset_admin_password.php
// Usage: php scripts/reset_admin_password.php -u admin_username -p 'NewPass123!'
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../app/models/User.php';

$shortopts = "u:p:";
$opts = getopt($shortopts);
$user = $opts['u'] ?? null;
$pass = $opts['p'] ?? null;
if (!$user || !$pass) {
    echo "Usage: php scripts/reset_admin_password.php -u username -p 'NewPass'\n";
    exit(2);
}

try {
    $u = User::findByUsernameOrEmail($user);
    if (!$u) { echo "User not found: $user\n"; exit(3); }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    if (User::updatePassword((int)$u['id'], $hash)) {
        echo "Password updated for user id=" . $u['id'] . "\n";
        exit(0);
    } else {
        echo "Failed to update password\n"; exit(4);
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n"; exit(1);
}
