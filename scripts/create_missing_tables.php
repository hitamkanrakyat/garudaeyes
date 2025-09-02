<?php
// scripts/create_missing_tables.php
// Apply SQL migration files from db/migrations in lexical order.
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../app/config.php';

try {
    $pdo = get_pdo();
    $dir = __DIR__ . '/../db/migrations';
    if (!is_dir($dir)) {
        echo "No migrations directory found\n";
        exit(0);
    }
    $files = glob($dir . '/*.sql');
    sort($files, SORT_STRING);
    foreach ($files as $f) {
        echo "Applying migration: " . basename($f) . "\n";
        $sql = file_get_contents($f);
        if ($sql === false) continue;
        // split by semicolon newline to avoid breaking complex statements
        $parts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($parts as $stmt) {
            if ($stmt === '') continue;
            try { $pdo->exec($stmt); } catch (Throwable $e) { echo "Migration statement failed: " . $e->getMessage() . "\n"; }
        }
    }
    echo "Migrations applied (if any)\n";
    exit(0);
} catch (Throwable $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
