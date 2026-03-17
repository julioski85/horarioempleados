<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    public static function loginAdmin(array $admin): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$admin['id'], 'role' => 'admin', 'name' => $admin['name']];
    }

    public static function loginEmployee(array $employee): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$employee['id'], 'role' => 'employee', 'name' => $employee['full_name']];
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function requireRole(string $role): void
    {
        $user = self::user();
        if (!$user || $user['role'] !== $role) {
            Response::redirect('/login');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }

    public static function adminByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, email, password_hash, is_active FROM admins WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function employeeByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, full_name, email, password_hash, is_active FROM employees WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
