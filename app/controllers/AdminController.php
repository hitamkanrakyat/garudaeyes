<?php
// app/controllers/AdminController.php
require_once __DIR__ . '/../models/User.php';

class AdminController
{
    public static function ensureAdmin()
    {
        if (empty($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo "Access denied";
            exit();
        }
    }
}
