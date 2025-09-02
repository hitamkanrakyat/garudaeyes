<?php
// app/models/PasswordReset.php
require_once __DIR__ . '/User.php';

class PasswordReset
{
    public static function createToken(int $user_id): string
    {
        $token = bin2hex(random_bytes(32));
        $pdo = get_pdo();
        $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at, used) VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR), 0)');
        $stmt->execute([':uid'=>$user_id, ':token'=>$token]);
        return $token;
    }

    public static function findByToken(string $token)
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT * FROM password_reset_tokens WHERE token = :t AND used = 0 AND expires_at > NOW() LIMIT 1');
        $stmt->execute([':t'=>$token]);
        return $stmt->fetch();
    }

    public static function markUsed(int $id)
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('UPDATE password_reset_tokens SET used = 1 WHERE id = :id');
        return $stmt->execute([':id'=>$id]);
    }
}
