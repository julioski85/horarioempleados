<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Response;
use App\Core\View;
use PDO;

final class EmployeeController
{
    public function dashboard(): void
    {
        Auth::requireRole('employee');
        $pdo = DB::pdo();
        $rows = $pdo->query('SELECT event_type,created_at FROM attendance ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
        $requests = $pdo->query('SELECT type,status,created_at FROM requests ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
        View::render('employee/dashboard', ['rows' => $rows, 'requests' => $requests, 'csrf' => Csrf::token(), 'title' => 'Mi panel']);
    }

    public function createRequest(): void
    {
        Auth::requireRole('employee');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/employee/dashboard');
        }
        $pdo = DB::pdo();
        $st = $pdo->prepare("INSERT INTO requests(employee_id,type,status,created_at) VALUES(1,?,'Pendiente',?)");
        $st->execute([$_POST['type'] ?? 'Permiso', date('Y-m-d H:i:s')]);
        Response::redirect('/employee/dashboard');
    }
}
