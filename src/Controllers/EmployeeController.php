<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;

final class EmployeeController
{
    public function dashboard(): void
    {
        Auth::requireRole('employee');
        $id = (int)Auth::user()['id'];
        $db = Database::connection();
        $history = $db->prepare('SELECT recorded_at, record_type, status FROM attendance_records WHERE employee_id=:id ORDER BY recorded_at DESC LIMIT 30');
        $history->execute(['id' => $id]);

        $requests = $db->prepare('SELECT request_type,start_date,end_date,status FROM requests WHERE employee_id=:id ORDER BY id DESC LIMIT 10');
        $requests->execute(['id' => $id]);
        Response::view('employee/dashboard', ['history' => $history->fetchAll(), 'requests' => $requests->fetchAll()]);
    }

    public function createRequest(): void
    {
        Auth::requireRole('employee');
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/employee/dashboard');
        }

        $stmt = Database::connection()->prepare('INSERT INTO requests (employee_id, request_type, start_date, end_date, reason, status, created_at, updated_at) VALUES (:employee_id,:type,:start_date,:end_date,:reason,"pending",NOW(),NOW())');
        $stmt->execute([
            'employee_id' => Auth::user()['id'],
            'type' => $_POST['request_type'] ?? 'permission',
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => $_POST['end_date'] ?? date('Y-m-d'),
            'reason' => trim($_POST['reason'] ?? ''),
        ]);

        Response::redirect('/employee/dashboard');
    }
}
