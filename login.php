<?php
// MVC aware login handler - delegates to AuthController and views
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$error = '';

// Accept POST when action is /login (front controller) or direct /login.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // accept either 'username' or 'email' from the form templates
  $email = trim((string) ($_POST['username'] ?? $_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $csrf = $_POST['csrf_token'] ?? '';

  if (!AuthController::check_csrf($csrf)) {
    $error = 'Invalid request.';
  } elseif (empty($email) || empty($password)) {
    $error = 'Lengkapi email dan password.';
  } else {
    if (AuthController::attempt_login($email, $password)) {
      // set flash message for successful login
      $_SESSION['flash'] = 'Login berhasil. Selamat datang!';
      header('Location: ' . (BASE_PATH ?: '') . '/dashboard');
      exit();
    }
    $error = 'Username atau password salah.';
  }
}

// Render view
require_once __DIR__ . '/app/views/login.php';

