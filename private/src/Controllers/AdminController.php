<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Response;
use App\Core\View;
use PDO;

final class AdminController
{
    public function dashboard(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();

        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $attendanceToday = $pdo->prepare('SELECT COUNT(*) FROM attendance_records WHERE DATE(recorded_at)=? AND is_void=0');
        $attendanceToday->execute([$today]);

        $kpis = [
            'employees' => (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
            'attendance_today' => (int) $attendanceToday->fetchColumn(),
            'pending_requests' => (int) $pdo->query("SELECT COUNT(*) FROM requests WHERE status='Pendiente'")->fetchColumn(),
            'active_rate' => 94,
        ];

        $rows = $pdo->query(
            "SELECT ar.id, e.full_name, ar.record_type AS event_type, ar.recorded_at AS created_at, ar.status
            FROM attendance_records ar
            JOIN employees e ON e.id = ar.employee_id
            WHERE ar.is_void = 0
            ORDER BY ar.recorded_at DESC
            LIMIT 8"
        )->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/dashboard', ['kpis' => $kpis, 'rows' => $rows, 'csrf' => Csrf::token(), 'title' => 'Dashboard']);
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

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $pin = (string) ($_POST['pin'] ?? '');
        $isActive = ($_POST['status'] ?? 'Activo') === 'Activo' ? 1 : 0;
        $shortId = trim((string) ($_POST['short_id'] ?? ''));

        if ($shortId === '') {
            $shortId = 'EMP-' . date('YmdHis');
        }

        $pdo = DB::pdo();
        $st = $pdo->prepare(
            'INSERT INTO employees(short_id,full_name,email,password_hash,pin_hash,area_id,team_id,base_photo_path,is_active) VALUES(?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $shortId,
            $fullName,
            $email,
            password_hash($pin !== '' ? $pin : bin2hex(random_bytes(6)), PASSWORD_DEFAULT),
            password_hash($pin, PASSWORD_DEFAULT),
            null,
            null,
            '/assets/uploads/base/avatar-base.svg',
            $isActive,
        ]);
        Response::redirect('/admin/employees');
    }


    public function updateEmployee(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/employees');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $shortId = trim((string) ($_POST['short_id'] ?? ''));
        $pin = trim((string) ($_POST['pin'] ?? ''));
        $isActive = ($_POST['status'] ?? 'Activo') === 'Activo' ? 1 : 0;

        if ($id <= 0 || $fullName === '' || $email === '') {
            Response::redirect('/admin/employees');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::redirect('/admin/employees');
        }

        $pdo = DB::pdo();

        $duplicateEmail = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE email=? AND id<>?');
        $duplicateEmail->execute([$email, $id]);
        if ((int) $duplicateEmail->fetchColumn() > 0) {
            Response::redirect('/admin/employees');
        }

        if ($shortId !== '') {
            $duplicateShortId = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE short_id=? AND id<>?');
            $duplicateShortId->execute([$shortId, $id]);
            if ((int) $duplicateShortId->fetchColumn() > 0) {
                Response::redirect('/admin/employees');
            }
        }

        if ($pin !== '') {
            $st = $pdo->prepare('UPDATE employees SET short_id=?,full_name=?,email=?,pin_hash=?,password_hash=?,is_active=? WHERE id=?');
            $st->execute([
                $shortId !== '' ? $shortId : null,
                $fullName,
                $email,
                password_hash($pin, PASSWORD_DEFAULT),
                password_hash($pin, PASSWORD_DEFAULT),
                $isActive,
                $id,
            ]);
        } else {
            $st = $pdo->prepare('UPDATE employees SET short_id=?,full_name=?,email=?,is_active=? WHERE id=?');
            $st->execute([$shortId !== '' ? $shortId : null, $fullName, $email, $isActive, $id]);
        }

        Response::redirect('/admin/employees');
    }

    public function requests(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();

        $requestTypeCol = $this->resolveColumn($pdo, 'requests', ['type', 'request_type', 'category']);
        $requestDateCol = $this->resolveColumn($pdo, 'requests', ['created_at', 'requested_at', 'submitted_at', 'recorded_at']);

        $requests = $pdo->query(
            "SELECT r.id,r.employee_id,r.status,r.{$requestTypeCol} AS type,r.{$requestDateCol} AS created_at,e.full_name
            FROM requests r
            JOIN employees e ON e.id=r.employee_id
            ORDER BY r.id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

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
        $rows = $pdo->query(
            'SELECT e.full_name,ar.record_type AS event_type,ar.recorded_at AS created_at
            FROM attendance_records ar
            JOIN employees e ON e.id=ar.employee_id
            WHERE ar.is_void=0
            ORDER BY ar.recorded_at DESC LIMIT 30'
        )->fetchAll(PDO::FETCH_ASSOC);
        View::render('admin/reports', ['rows' => $rows, 'title' => 'Reportes']);
    }

    public function exportCsv(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();
        $rows = $pdo->query(
            'SELECT e.full_name,ar.record_type AS event_type,ar.recorded_at AS created_at
            FROM attendance_records ar
            JOIN employees e ON e.id=ar.employee_id
            WHERE ar.is_void=0
            ORDER BY ar.recorded_at DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
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
