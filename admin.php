<?php
// admin.php - simple router for admin actions
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/app/controllers/AdminController.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

AdminController::ensureAdmin();

$path = $_GET['p'] ?? '';

switch ($path) {
    case 'users':
        require __DIR__ . '/app/views/admin_users.php';
        break;
    case 'users/edit':
        require __DIR__ . '/app/views/admin_user_form.php';
        break;
    case 'users/save':
        // handle save (POST)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthController::check_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(400);
            echo 'Invalid request';
            exit();
        }
        $id = (int)($_POST['id'] ?? 0);
        $data = ['username'=>$_POST['username'] ?? '', 'fullname'=>$_POST['fullname'] ?? '', 'role'=>$_POST['role'] ?? 'user', 'email'=>$_POST['email'] ?? ''];
        if ($id) {
            User::update($id, $data);
        } else {
            User::create($data['username'], bin2hex(random_bytes(8)), $data['fullname'], $data['role'], $data['email']);
        }
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/admin?p=users');
        break;
    case 'users/delete':
        // require POST with csrf (use GET id but require csrf)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!AuthController::check_csrf((string)($_POST['csrf_token'] ?? ''))) {
                http_response_code(400); echo 'Invalid request'; exit();
            }
            $id = (int)($_POST['id'] ?? 0);
            if ($id) User::delete($id);
        } else {
            // fallback: if GET used, deny
            http_response_code(405); echo 'Method not allowed'; exit();
        }
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/admin?p=users');
        break;
    case 'login_attempts':
        require __DIR__ . '/app/views/admin_login_attempts.php';
        break;
    case 'actions/unlock_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthController::check_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(400); echo 'Invalid request'; exit();
        }
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid) {
            $pdo = get_pdo();
            $stmt = $pdo->prepare('UPDATE users SET locked_until = NULL WHERE id = :id');
            $stmt->execute([':id' => $uid]);
        }
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/admin?p=login_attempts');
        break;
    default:
        echo 'Admin area';
}
