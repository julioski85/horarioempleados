<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(24));
        }

        return $_SESSION['_csrf'];
    }

    public static function check(?string $token): bool
    {
        return hash_equals($_SESSION['_csrf'] ?? '', (string) $token);
    }
}
