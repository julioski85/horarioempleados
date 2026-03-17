<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name($_ENV['SESSION_NAME'] ?? 'horarioempleados_session');
    session_start();
}

require_once __DIR__ . '/../src/Core/Env.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Csrf.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/Audit.php';
require_once __DIR__ . '/../src/Core/Response.php';
require_once __DIR__ . '/../src/Core/Helpers.php';

\App\Core\Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City');
