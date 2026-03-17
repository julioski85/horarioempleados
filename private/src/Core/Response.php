<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): void
    {
        header('Location: ' . Url::to($path));
        exit;
    }

    public static function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
