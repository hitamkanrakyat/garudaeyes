<?php
// Front controller: route basic paths to appropriate scripts/views
require_once __DIR__ . '/init.php';

// Normalize request path and application base (supports subfolder installs)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];
$base = rtrim(dirname($scriptName), '/\\');
$path = $requestUri;
if ($base !== '' && strpos($path, $base) === 0) {
    $path = substr($path, strlen($base));
}
$path = trim($path, '/');

// If request targets an API file under /api, include it directly when present.
if (strpos($path, 'api/') === 0) {
    $apiFile = __DIR__ . '/' . $path;
    if (is_file($apiFile)) {
        include $apiFile;
        exit;
    }
}

switch ($path) {
    case '':
    case 'home':
        // Landing page: always show public home/landing for root.
        include __DIR__ . '/app/views/home.php';
        break;
    case 'login':
        include __DIR__ . '/login.php';
        break;
    case 'dashboard':
        include __DIR__ . '/dashboard.php';
        break;
    case 'logout':
        include __DIR__ . '/logout.php';
        break;
    default:
        // Try to serve file if exists, otherwise 404
        $file = __DIR__ . '/' . $path;
        if (is_file($file)) {
            return false; // let Apache serve it
        }
        http_response_code(404);
        include __DIR__ . '/404.html';
}
