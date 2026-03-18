<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\EmployeeController;
use App\Controllers\KioskController;
use App\Core\Response;
use App\Core\Url;

require_once __DIR__ . '/private/config/bootstrap.php';
require_once __DIR__ . '/private/src/Controllers/AuthController.php';
require_once __DIR__ . '/private/src/Controllers/AdminController.php';
require_once __DIR__ . '/private/src/Controllers/EmployeeController.php';
require_once __DIR__ . '/private/src/Controllers/KioskController.php';
require_once __DIR__ . '/private/src/Services/AttendanceService.php';

$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$basePath = Url::basePath();

if ($basePath !== '' && str_starts_with($uriPath, $basePath)) {
    $uriPath = substr($uriPath, strlen($basePath)) ?: '/';
}

$path = '/' . ltrim($uriPath, '/');
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
        '/admin/attendance' => fn () => (new AdminController())->attendanceSettings(),
        '/admin/reports/export-csv' => fn () => (new AdminController())->exportCsv(),
        '/employee/dashboard' => fn () => (new EmployeeController())->dashboard(),
    ],
    'POST' => [
        '/login' => fn () => (new AuthController())->login(),
        '/admin/employees/save' => fn () => (new AdminController())->saveEmployee(),
        '/admin/employees/update' => fn () => (new AdminController())->updateEmployee(),
        '/admin/requests/status' => fn () => (new AdminController())->updateRequestStatus(),
        '/admin/attendance/save' => fn () => (new AdminController())->saveAttendanceSettings(),
        '/admin/employees/schedule' => fn () => (new AdminController())->saveEmployeeSchedule(),
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
