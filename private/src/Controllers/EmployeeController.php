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
        $user = Auth::user();
        $employeeId = (int) ($user['id'] ?? 0);

        $pdo = DB::pdo();

        $rowsSt = $pdo->prepare('SELECT record_type AS event_type, recorded_at AS created_at FROM attendance_records WHERE employee_id=? AND is_void=0 ORDER BY recorded_at DESC LIMIT 10');
        $rowsSt->execute([$employeeId]);
        $rows = $rowsSt->fetchAll(PDO::FETCH_ASSOC);

        $requestTypeCol = $this->resolveColumn($pdo, 'requests', ['type', 'request_type', 'category']);
        $requestDateCol = $this->resolveColumn($pdo, 'requests', ['created_at', 'requested_at', 'submitted_at', 'recorded_at']);

        $reqSt = $pdo->prepare("SELECT {$requestTypeCol} AS type,status,{$requestDateCol} AS created_at FROM requests WHERE employee_id=? ORDER BY id DESC LIMIT 5");
        $reqSt->execute([$employeeId]);
        $requests = $reqSt->fetchAll(PDO::FETCH_ASSOC);

        View::render('employee/dashboard', ['rows' => $rows, 'requests' => $requests, 'csrf' => Csrf::token(), 'title' => 'Mi panel']);
    }

    public function createRequest(): void
    {
        Auth::requireRole('employee');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/employee/dashboard');
        }

        $user = Auth::user();
        $employeeId = (int) ($user['id'] ?? 0);
        $pdo = DB::pdo();

        $requestTypeCol = $this->resolveColumn($pdo, 'requests', ['type', 'request_type', 'category']);
        $requestDateCol = $this->resolveColumn($pdo, 'requests', ['created_at', 'requested_at', 'submitted_at', 'recorded_at']);

        $st = $pdo->prepare("INSERT INTO requests(employee_id,{$requestTypeCol},status,{$requestDateCol}) VALUES(?,?,'Pendiente',?)");
        $st->execute([$employeeId, $_POST['type'] ?? 'Permiso', date('Y-m-d H:i:s')]);
        Response::redirect('/employee/dashboard');
    }

    private function resolveColumn(PDO $pdo, string $table, array $candidates): string
    {
        $st = $pdo->query("SHOW COLUMNS FROM {$table}");
        $cols = array_map(static fn(array $c) => $c['Field'], $st->fetchAll(PDO::FETCH_ASSOC));

        foreach ($candidates as $column) {
            if (in_array($column, $cols, true)) {
                return $column;
            }
        }

        throw new \RuntimeException("No se encontró columna compatible en {$table}. Candidatas: " . implode(', ', $candidates));
    }
}
