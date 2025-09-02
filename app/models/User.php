<?php
// app/models/User.php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

class User
{
    // Find by username OR email
    public static function findByUsernameOrEmail(string $u)
    {
        $pdo = get_pdo();
        // Use distinct parameter names for username and email to avoid PDO driver issues when the
        // same named parameter appears multiple times in the query.
        $stmt = $pdo->prepare('SELECT id, username, email, password, fullname, role, current_session FROM users WHERE username = :u OR email = :e LIMIT 1');
        $stmt->execute([':u' => $u, ':e' => $u]);
        return $stmt->fetch();
    }

    // Backwards-compatible alias
    public static function findByUsername(string $username)
    {
        return self::findByUsernameOrEmail($username);
    }

    // Create user (returns id)
    public static function create(string $username, string $password, string $fullname = '', string $role = 'user', string $email = ''): int
    {
        $pdo = get_pdo();
        // basic validation
        $username = trim($username);
        $email = trim($email);
        if ($username === '') {
            throw new InvalidArgumentException('Username is required');
        }
        // validate email when provided
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
        // uniqueness checks
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u OR (email = :e AND :e != "") LIMIT 1');
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Username or email already exists');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, fullname, role, created_at) VALUES (:u, :e, :p, :f, :r, NOW())');
        $stmt->execute([':u' => $username, ':e' => $email, ':p' => $hash, ':f' => $fullname, ':r' => $role]);
        return (int)$pdo->lastInsertId();
    }

    public static function updatePassword(int $id, string $newHash): bool
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('UPDATE users SET password = :p WHERE id = :id');
        return $stmt->execute([':p' => $newHash, ':id' => $id]);
    }

    // session helpers to enforce single-session per user
    public static function setCurrentSession(int $id, ?string $session_id): bool
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('UPDATE users SET current_session = :s WHERE id = :id');
        return $stmt->execute([':s' => $session_id, ':id' => $id]);
    }

    public static function getCurrentSession(int $id): ?string
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT current_session FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch();
        return $r ? ($r['current_session'] ?? null) : null;
    }

    public static function clearCurrentSession(int $id): bool
    {
        return self::setCurrentSession($id, null);
    }

    // Admin helpers
    public static function all(int $limit = 50, int $offset = 0): array
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id, username, email, fullname, role, created_at FROM users ORDER BY id DESC LIMIT :l OFFSET :o');
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countAll(): int
    {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        return (int)$stmt->fetchColumn();
    }

    public static function findById(int $id)
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id, username, email, fullname, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('UPDATE users SET username=:username, email=:email, fullname=:fullname, role=:role WHERE id=:id');
        return $stmt->execute([':username' => $data['username'], ':email' => ($data['email'] ?? ''), ':fullname' => $data['fullname'], ':role' => $data['role'], ':id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public static function setPasswordById(int $id, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return self::updatePassword($id, $hash);
    }
}
