<?php

declare(strict_types=1);

namespace App\Core;

final class Url
{
    public static function basePath(): string
    {
        $configured = trim((string) Env::get('APP_BASE_PATH', ''));
        if ($configured !== '') {
            return '/' . trim($configured, '/');
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '.') {
            return '';
        }

        return '/' . trim($dir, '/');
    }

    public static function to(string $path = '/'): string
    {
        $base = self::basePath();
        $normalizedPath = '/' . ltrim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        return $base . $normalizedPath;
    }
}

