<?php
require_once __DIR__ . '/init.php';

if (!isset($_SESSION['username'])) {
    header('Location: ' . (BASE_PATH ?: '') . '/login');
    exit();
}

// render dashboard view
require_once __DIR__ . '/app/views/dashboard.php';
