<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function requireRole(string $role): void
    {
        $user = self::user();
        if (!$user || $user['role'] !== $role) {
            Response::redirect('/login');
        }
    }
}
