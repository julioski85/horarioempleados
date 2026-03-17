<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\EmployeeController;
use App\Controllers\KioskController;
use App\Core\Response;

require_once __DIR__ . '/private/config/bootstrap.php';
require_once __DIR__ . '/private/src/Controllers/AuthController.php';
require_once __DIR__ . '/private/src/Controllers/AdminController.php';
require_once __DIR__ . '/private/src/Controllers/EmployeeController.php';
require_once __DIR__ . '/private/src/Controllers/KioskController.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET' => [
        '/' => fn () => Response::redirect('/login'),
        '/login' => fn () => (new AuthController())->showLogin(),
        '/logout' => fn () => (new AuthController())->logout(),
        '/kiosk' => fn () => (new KioskController())->index(),
        '/kiosk/search' => fn () => (new KioskController())->search(),
        '/kiosk/next-action' => fn () => (new KioskController())->nextAction(),
        '/admin/dashboard' => fn () => (new AdminController())->dashboard(),
        '/admin/employees' => fn () => (new AdminController())->employees(),
        '/admin/requests' => fn () => (new AdminController())->requests(),
        '/admin/reports' => fn () => (new AdminController())->reports(),
        '/admin/reports/export-csv' => fn () => (new AdminController())->exportCsv(),
        '/employee/dashboard' => fn () => (new EmployeeController())->dashboard(),
    ],
    'POST' => [
        '/login/admin' => fn () => (new AuthController())->loginAdmin(),
        '/login/employee' => fn () => (new AuthController())->loginEmployee(),
        '/admin/employees/save' => fn () => (new AdminController())->saveEmployee(),
        '/admin/requests/status' => fn () => (new AdminController())->updateRequestStatus(),
        '/employee/request' => fn () => (new EmployeeController())->createRequest(),
        '/kiosk/register' => fn () => (new KioskController())->register(),
    ],
];

if (isset($routes[$method][$path])) {
    $routes[$method][$path]();
    exit;
}

http_response_code(404);
echo 'Ruta no encontrada';
