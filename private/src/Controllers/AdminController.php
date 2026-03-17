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
        $stToday = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE substr(created_at,1,10)=?');
        $stToday->execute([$today]);

        $kpis = [
            'employees' => (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
            'attendance_today' => (int) $stToday->fetchColumn(),
            'pending_requests' => (int) $pdo->query("SELECT COUNT(*) FROM requests WHERE status='Pendiente'")->fetchColumn(),
            'active_rate' => 94,
        ];

        $rows = $pdo->query('SELECT a.id,e.full_name,a.event_type,a.created_at FROM attendance a JOIN employees e ON e.id=a.employee_id ORDER BY a.id DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
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
}
