<?php
// scripts/import_garuda.php
// Run with: /opt/lampp/bin/php scripts/import_garuda.php
require_once __DIR__ . '/../app/config.php';

$path = __DIR__ . '/../garuda.sql';
if (!file_exists($path)) {
    echo "garuda.sql not found at: $path\n";
    exit(1);
}
$sql = file_get_contents($path);
if ($sql === false) {
    echo "Failed to read garuda.sql\n";
    exit(1);
}

// connect without database to allow CREATE DATABASE
$dsn = 'mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : '3306') . ';charset=' . DB_CHARSET;
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    echo "PDO connect failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Split statements on ";\n" which should be safe for this seed file
$stmts = preg_split('/;\s*\n/', $sql);
$execCount = 0;
foreach ($stmts as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    try {
        $pdo->exec($stmt);
        $execCount++;
    } catch (PDOException $e) {
        // ignore errors about 'Can't create database; database exists' etc, but show others
        $msg = $e->getMessage();
        if (stripos($msg, 'database exists') !== false || stripos($msg, 'already exists') !== false) {
            // ignore
        } else {
            echo "Warning executing statement: " . substr($stmt,0,120) . " ...\n";
            echo "  Error: " . $msg . "\n";
        }
    }
}

echo "Executed approx $execCount statements from garuda.sql\n";

// quick check: is admin present?
try {
    $pdo2 = new PDO('mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : '3306') . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $res = $pdo2->query("SELECT username FROM users WHERE username='admin@garuda.local' LIMIT 1")->fetchColumn();
    if ($res) {
        echo "Admin user exists: $res\n";
    } else {
        echo "Admin user not found after import.\n";
    }
} catch (PDOException $e) {
    echo "Could not verify users table: " . $e->getMessage() . "\n";
}

return 0;
