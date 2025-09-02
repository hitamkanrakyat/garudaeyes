<?php
// init.php — include this at the very top of every PHP page (before any HTML/output)
require_once __DIR__ . '/app/config.php';

// Application base path (supports subfolder installs like /garudaeyes)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(dirname($scriptName), '/\\');
if ($base === '.' || $base === '/') $base = '';
define('BASE_PATH', $base);

// Set cookie params *SEBELUM* session_start()
$cookieParams = [
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => SITE_DOMAIN,
    'secure'   => USE_HTTPS,
    'httponly' => true,
    'samesite' => 'Lax'
];

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params($cookieParams);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (USE_HTTPS) ini_set('session.cookie_secure', '1');
    session_start();
}

// force https if configured
if (USE_HTTPS && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirect);
    exit();
}

// Enforce single-session per user: if logged in, verify session matches DB
if (!empty($_SESSION['user_id'])) {
    // lazy-load User model
    if (file_exists(__DIR__ . '/app/models/User.php')) {
        require_once __DIR__ . '/app/models/User.php';
        try {
            $current = User::getCurrentSession((int)$_SESSION['user_id']);
            if ($current !== null && $current !== session_id()) {
                // session mismatch: force logout
                session_unset();
                session_destroy();
                // restart a fresh session to show message
                session_start();
                $_SESSION['flash'] = 'Sesi Anda telah berakhir karena login di perangkat lain.';
            }
        } catch (Exception $e) {
            // ignore DB errors here; don't reveal to users
        }
    }
}
