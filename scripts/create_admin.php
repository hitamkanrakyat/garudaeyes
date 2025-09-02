<?php
// scripts/create_admin.php
require_once __DIR__ . '/../app/config.php';

if (php_sapi_name() !== 'cli') {
    echo "Run this script from CLI: php create_admin.php\n";
    exit(1);
}

$shortopts = "u:p:n:e:"; // user, pass, name, email
$options = getopt($shortopts);

$username = $options['u'] ?? null;
$password = $options['p'] ?? null;
$fullname = $options['n'] ?? 'Administrator';
$email = $options['e'] ?? ($username ?: 'admin@example.local');

if (!$username || !$password) {
    echo "Usage: php create_admin.php -u username -p password [-n \"Full Name\"]\n";
    exit(1);
}

require_once __DIR__ . '/../app/models/User.php';

try {
    $id = User::create($username, $password, $fullname, 'admin', $email);
    echo "Created user id=$id (username=$username email=$email)\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
