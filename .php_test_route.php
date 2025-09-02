<?php
// temporary test to simulate request
// Allow TEST_REQUEST_URI to include query string, e.g. /admin?p=users
$uri = getenv('TEST_REQUEST_URI') ?: '/';
$parts = explode('?', $uri, 2);
$_SERVER['REQUEST_URI'] = $parts[0];
$_SERVER['QUERY_STRING'] = $parts[1] ?? '';
// parse query string into $_GET
parse_str($_SERVER['QUERY_STRING'], $_GET);
$_SERVER['SCRIPT_NAME'] = '/index.php';
chdir(__DIR__);
// Optionally simulate logged-in admin
if (getenv('TEST_AS_ADMIN')) {
	if (session_status() === PHP_SESSION_NONE) session_start();
	$_SESSION['username'] = 'admin@garuda.local';
	$_SESSION['role'] = 'admin';
}

// If testing admin route, include admin.php
if (strpos($uri, '/admin') === 0 || strpos($uri, '/admin?') === 0) {
	include __DIR__ . '/admin.php';
} else {
	include __DIR__ . '/index.php';
}
