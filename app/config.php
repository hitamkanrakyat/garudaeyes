<?php
// app/config.php - shared configuration and PDO helper
declare(strict_types=1);

// Database configuration - update if different
// If you're running on LAMPP/XAMPP for Linux, we auto-detect and use typical defaults
if (is_dir('/opt/lampp')) {
    // LAMPP typical defaults: root user, no password, MySQL on 3306
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    // default DB for LAMPP deployment (change if you prefer)
    define('DB_NAME', getenv('GARUDA_DB') ?: 'garudaeyes');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('USE_HTTPS', false);
} else {
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', getenv('GARUDA_DB') ?: 'garudaeyes');
    define('DB_USER', 'sql_garudaeyes_c');
    define('DB_PASS', '5580a5bb27d438');
}
define('DB_CHARSET', 'utf8mb4');

// Basic site config
define('SITE_DOMAIN', '');
if (!defined('USE_HTTPS')) {
    define('USE_HTTPS', true);
}

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Build DSN with optional port
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : '3306') . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            ensure_schema($pdo);
        return $pdo;
    } catch (PDOException $e) {
        // If database does not exist (1049), try to create it (useful for LAMPP quick setup)
        if ($e->getCode() === '1049' || strpos($e->getMessage(), 'Unknown database') !== false) {
            try {
                // connect without db
                $dsn2 = 'mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : '3306');
                $pdo2 = new PDO($dsn2, DB_USER, DB_PASS, $options);
                $pdo2->exec('CREATE DATABASE IF NOT EXISTS `' . addslashes(DB_NAME) . '` DEFAULT CHARACTER SET ' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci');
                // retry
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    ensure_schema($pdo);
                return $pdo;
            } catch (PDOException $e2) {
                throw $e2;
            }
        }
        throw $e;
    }
}

function ensure_schema(PDO $pdo)
{
    static $ran = false;
    if ($ran) return;
    $ran = true;

    // check if users table exists
    try {
        $res = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
        if (empty($res)) {
            $sql = file_get_contents(__DIR__ . '/../db/init.sql');
            if ($sql !== false) {
                // split by semicolon may be naive but good enough for this small init.sql
                $parts = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($parts as $stmt) {
                    if ($stmt !== '') $pdo->exec($stmt);
                }
            }
        }
    } catch (Exception $e) {
        // ignore — leave to manual migration
    }
}

// Simple content security policy header (can be tuned)
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:");
// HSTS when HTTPS is in use
if (defined('USE_HTTPS') && USE_HTTPS) {
    // max-age 1 year, include subdomains; set only when serving over TLS
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Helper to build URLs respecting BASE_PATH when available
if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $base = '';
        if (defined('BASE_PATH')) $base = BASE_PATH;
        if ($base === null) $base = '';
        return ($base ? $base : '') . '/' . $path;
    }
}
