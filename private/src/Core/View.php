<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $params = [], string $layout = 'layouts/app'): void
    {
        extract($params, EXTR_SKIP);
        $viewPath = __DIR__ . '/../../views/' . $view . '.php';
        $layoutPath = __DIR__ . '/../../views/' . $layout . '.php';

        ob_start();
        require $viewPath;
        $content = ob_get_clean();
        require $layoutPath;
    }
}
