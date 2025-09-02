<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/app/models/PasswordReset.php';
require_once __DIR__ . '/app/models/User.php';

$token = $_GET['token'] ?? '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $pr = PasswordReset::findByToken($token);
    if ($pr) {
        $new = $_POST['password'] ?? '';
        if (strlen($new) < 8) {
            $message = 'Password harus minimal 8 karakter.';
        } else {
            User::setPasswordById((int)$pr['user_id'], $new);
            PasswordReset::markUsed((int)$pr['id']);
            $_SESSION['flash'] = 'Password berhasil direset, silakan login.';
            header('Location: /login');
            exit();
        }
    } else {
        $message = 'Token tidak valid atau kadaluarsa.';
    }
}

require_once __DIR__ . '/app/views/layout.php';
echo '<div class="container"><div class="card">';
echo '<h2>Reset Password</h2>';
if ($message) echo '<div class="flash">'.htmlspecialchars($message).'</div>';
if ($token) {
    echo '<form method="post">';
    echo '<input type="hidden" name="token" value="'.htmlspecialchars($token).'">';
    echo '<label>Password baru</label><input name="password" type="password" required style="width:100%;padding:8px;margin:6px 0">';
    echo '<div style="margin-top:12px"><button class="btn" type="submit">Reset</button></div>';
    echo '</form>';
} else {
    echo '<p class="muted">Token tidak disediakan.</p>';
}

echo '</div></div>';
