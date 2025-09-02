<?php
// app/controllers/AuthController.php
declare(strict_types=1);
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public static function csrf_token(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['csrf_token'];
    }

    public static function check_csrf(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function attempt_login(string $username, string $password): bool
    {
        // server-side rate limiting using DB logging
        $pdo = null;
        try { $pdo = get_pdo(); } catch (Exception $e) { /* ignore, continue */ }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Count failures in last 15 minutes for this username or IP
        $failedCount = 0;
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND (username = :u OR ip = :ip)');
            $stmt->execute([':u' => $username, ':ip' => $ip]);
            $failedCount = (int)$stmt->fetchColumn();
        }

        $MAX_FAIL = 10;
        if ($failedCount >= $MAX_FAIL) {
            // Too many failures: if user exists, set locked_until
            if ($user && isset($user['id'])) {
                try {
                    $pdo->prepare('UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = :id')->execute([':id' => $user['id']]);
                    // notify user by email if available
                    if (!empty($user['email'])) {
                        $to = $user['email'];
                        $sub = 'Account temporarily locked';
                        $msg = "Akun Anda telah dikunci sementara karena terlalu banyak percobaan login gagal. Silakan coba lagi setelah 30 menit.";
                        // try mail(), fallback to log
                        if (!@mail($to, $sub, $msg)) {
                            $logdir = __DIR__ . '/../../logs';
                            if (!is_dir($logdir)) @mkdir($logdir, 0700, true);
                            @file_put_contents($logdir . '/notify.log', date('c') . " - lockout email to $to failed, message: $msg\n", FILE_APPEND);
                        }
                    }
                } catch (Exception $e) { /* ignore */ }
            }
            return false; // too many recent failures
        }

        $user = User::findByUsernameOrEmail($username);
        if ($user && !empty($user['locked_until'])) {
            try {
                $lu = new DateTimeImmutable($user['locked_until']);
                $now = new DateTimeImmutable('now');
                if ($lu > $now) {
                    // still locked
                    return false;
                }
            } catch (Exception $e) {
                // ignore parsing errors
            }
        }
        if ($user) {
            // if password stored as MD5 (legacy) — DB may have 32-char hex
            $stored = $user['password'];
            if (strlen($stored) === 32 && hash_equals(md5($password), $stored)) {
                // upgrade hash
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                User::updatePassword((int)$user['id'], $newHash);
                $match = true;
            } else {
                $match = password_verify($password, $stored);
            }

            if ($match) {
                // successful
                // log success
                if ($pdo) {
                    $stmt = $pdo->prepare('INSERT INTO login_attempts (user_id, username, ip, success) VALUES (:uid, :u, :ip, 1)');
                    $stmt->execute([':uid' => $user['id'], ':u' => $username, ':ip' => $ip]);
                }
                // Single-session enforcement: record current session id in DB
                session_regenerate_id(true);
                $sid = session_id();
                // set DB current_session to this session id
                User::setCurrentSession((int)$user['id'], $sid);

                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['fullname'] = $user['fullname'] ?? '';
                $_SESSION['role'] = $user['role'] ?? 'user';
                // reset attempts
                $_SESSION['login_attempts'] = 0;
                return true;
            }
        }

        // log failure
        try {
            if ($pdo) {
                $stmt = $pdo->prepare('INSERT INTO login_attempts (user_id, username, ip, success) VALUES (:uid, :u, :ip, 0)');
                $stmt->execute([':uid' => $user['id'] ?? null, ':u' => $username, ':ip' => $ip]);
            }
        } catch (Exception $e) { /* ignore logging errors */ }

        // increment session fallback
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt'] = time();
        return false;
    }

    public static function logout()
    {
        session_unset();
        session_destroy();
    }
}
