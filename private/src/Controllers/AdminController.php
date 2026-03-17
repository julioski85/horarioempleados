<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Response;
use App\Core\View;
use PDO;
use PDOException;

final class AdminController
{
    public function dashboard(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();

        try {
            $tables = $this->resolveAdminTables($pdo);
            $today = (new \DateTimeImmutable())->format('Y-m-d');

            $stToday = $pdo->prepare("SELECT COUNT(*) FROM {$tables['attendance']} WHERE DATE({$tables['attendance_created_at']}) = ?");
            $stToday->execute([$today]);

            $kpis = [
                'employees' => (int) $pdo->query("SELECT COUNT(*) FROM {$tables['employees']}")->fetchColumn(),
                'attendance_today' => (int) $stToday->fetchColumn(),
                'pending_requests' => (int) $pdo->query("SELECT COUNT(*) FROM {$tables['requests']} WHERE {$tables['requests_status']}='Pendiente'")->fetchColumn(),
                'active_rate' => 94,
            ];

            $rows = $pdo->query(
                "SELECT a.{$tables['attendance_id']} AS id,e.{$tables['employee_name']} AS full_name,a.{$tables['attendance_event']} AS event_type,a.{$tables['attendance_created_at']} AS created_at
                FROM {$tables['attendance']} a
                JOIN {$tables['employees']} e ON e.{$tables['employee_id']}=a.{$tables['attendance_employee_id']}
                ORDER BY a.{$tables['attendance_id']} DESC LIMIT 8"
            )->fetchAll(PDO::FETCH_ASSOC);

            View::render('admin/dashboard', ['kpis' => $kpis, 'rows' => $rows, 'csrf' => Csrf::token(), 'title' => 'Dashboard']);
        } catch (\RuntimeException|PDOException $e) {
            error_log('Admin dashboard error: ' . $e->getMessage());
            http_response_code(500);
            View::render('admin/dashboard', [
                'kpis' => ['employees' => 0, 'attendance_today' => 0, 'pending_requests' => 0, 'active_rate' => 0],
                'rows' => [],
                'csrf' => Csrf::token(),
                'title' => 'Dashboard',
                'dashboard_error' => 'No se pudo cargar el dashboard. Revisa la estructura de tablas de asistencia/empleados/solicitudes.',
            ]);
        }
    }

    public function employees(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();
        $employees = $pdo->query('SELECT * FROM employees ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        View::render('admin/employees', ['employees' => $employees, 'csrf' => Csrf::token(), 'title' => 'Empleados']);
    }

    public function saveEmployee(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/employees');
        }
        $pdo = DB::pdo();
        $st = $pdo->prepare('INSERT INTO employees(full_name,email,role,pin,status,photo_path) VALUES(?,?,?,?,?,?)');
        $st->execute([
            $_POST['full_name'] ?? '',
            $_POST['email'] ?? '',
            'empleado',
            $_POST['pin'] ?? '',
            $_POST['status'] ?? 'Activo',
            '/assets/uploads/base/avatar-base.svg',
        ]);
        Response::redirect('/admin/employees');
    }

    public function requests(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();
        $requests = $pdo->query('SELECT r.*,e.full_name FROM requests r JOIN employees e ON e.id=r.employee_id ORDER BY r.id DESC')->fetchAll(PDO::FETCH_ASSOC);
        View::render('admin/requests', ['requests' => $requests, 'csrf' => Csrf::token(), 'title' => 'Solicitudes']);
    }

    public function updateRequestStatus(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/requests');
        }
        $pdo = DB::pdo();
        $st = $pdo->prepare('UPDATE requests SET status=? WHERE id=?');
        $st->execute([$_POST['status'] ?? 'Pendiente', (int) ($_POST['id'] ?? 0)]);
        Response::redirect('/admin/requests');
    }

    public function reports(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();
        $rows = $pdo->query('SELECT e.full_name,a.event_type,a.created_at FROM attendance a JOIN employees e ON e.id=a.employee_id ORDER BY a.id DESC LIMIT 30')->fetchAll(PDO::FETCH_ASSOC);
        View::render('admin/reports', ['rows' => $rows, 'title' => 'Reportes']);
    }

    public function exportCsv(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();
        $rows = $pdo->query('SELECT e.full_name,a.event_type,a.created_at FROM attendance a JOIN employees e ON e.id=a.employee_id ORDER BY a.id DESC')->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte-asistencia.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Empleado', 'Evento', 'Fecha']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['full_name'], $r['event_type'], $r['created_at']]);
        }
        fclose($out);
        exit;
    }

    private function resolveAdminTables(PDO $pdo): array
    {
        $employees = $this->resolveTable($pdo, ['employees', 'empleados', 'users']);
        $attendance = $this->resolveTable($pdo, ['attendance', 'asistencias', 'asistencia']);
        $requests = $this->resolveTable($pdo, ['requests', 'solicitudes', 'request']);

        return [
            'employees' => $employees,
            'attendance' => $attendance,
            'requests' => $requests,
            'employee_id' => $this->resolveColumn($pdo, $employees, ['id', 'employee_id', 'empleado_id', 'user_id']),
            'employee_name' => $this->resolveColumn($pdo, $employees, ['full_name', 'nombre_completo', 'name', 'nombre']),
            'attendance_id' => $this->resolveColumn($pdo, $attendance, ['id', 'attendance_id']),
            'attendance_employee_id' => $this->resolveColumn($pdo, $attendance, ['employee_id', 'empleado_id', 'user_id']),
            'attendance_event' => $this->resolveColumn($pdo, $attendance, ['event_type', 'tipo', 'tipo_evento']),
            'attendance_created_at' => $this->resolveColumn($pdo, $attendance, ['created_at', 'fecha_hora', 'fecha']),
            'requests_status' => $this->resolveColumn($pdo, $requests, ['status', 'estado']),
        ];
    }

    private function resolveTable(PDO $pdo, array $candidates): string
    {
        foreach ($candidates as $table) {
            try {
                $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
                return $table;
            } catch (PDOException) {
                continue;
            }
        }

        throw new \RuntimeException('No se encontró tabla compatible. Candidatas: ' . implode(', ', $candidates));
    }

    private function resolveColumn(PDO $pdo, string $table, array $candidates): string
    {
        try {
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $st = $pdo->query("PRAGMA table_info({$table})");
                $cols = array_map(static fn(array $c) => $c['name'], $st->fetchAll(PDO::FETCH_ASSOC));
            } else {
                $st = $pdo->query("SHOW COLUMNS FROM {$table}");
                $cols = array_map(static fn(array $c) => $c['Field'], $st->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (PDOException $e) {
            throw new \RuntimeException("No se pudieron leer columnas de {$table}: {$e->getMessage()}");
        }

        foreach ($candidates as $column) {
            if (in_array($column, $cols, true)) {
                return $column;
            }
        }

        throw new \RuntimeException("No se encontró columna compatible en {$table}. Candidatas: " . implode(', ', $candidates));
    }
}
