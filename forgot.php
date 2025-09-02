<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/models/PasswordReset.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $user = get_pdo()->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
        $user->execute([':u'=>$email]);
        $u = $user->fetch();
        if ($u) {
            $token = PasswordReset::createToken((int)$u['id']);
            // In production, send email. For now, show token link
            $message = 'Token dibuat. Gunakan link: /reset.php?token=' . urlencode($token);
        } else {
            $message = 'Email tidak ditemukan.';
        }
    }
}

require_once __DIR__ . '/app/views/layout.php';
echo '<div class="container"><div class="card">';
echo '<h2>Forgot Password</h2>';
if ($message) echo '<div class="flash">'.htmlspecialchars($message).'</div>';
echo '<form method="post"><label>Email</label><input name="email" required style="width:100%;padding:8px;margin:6px 0"><div style="margin-top:12px"><button class="btn" type="submit">Buat Token</button></div></form>';
echo '</div></div>';
