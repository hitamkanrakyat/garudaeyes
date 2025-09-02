<?php
// scripts/apply_init_cli.php
// Run from the project root or copy to deployment and run with: php scripts/apply_init_cli.php
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../init.php';
try {
    $pdo = get_pdo();
    $sql = file_get_contents(__DIR__ . '/../db/init.sql');
    if ($sql === false) {
        fwrite(STDERR, "init.sql not found\n");
        exit(2);
    }
    $parts = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($parts as $stmt) {
        if ($stmt !== '') $pdo->exec($stmt);
    }
    echo "OK: schema applied or already present\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
