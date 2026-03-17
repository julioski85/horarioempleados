<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;

final class AdminController
{
    public function dashboard(): void
    {
        Auth::requireRole('admin');
        $db = Database::connection();

        $kpis = [
            'employees_active' => (int)$db->query('SELECT COUNT(*) FROM employees WHERE is_active=1')->fetchColumn(),
            'entries_today' => (int)$db->query("SELECT COUNT(*) FROM attendance_records WHERE DATE(recorded_at)=CURDATE() AND record_type='entry' AND is_void=0")->fetchColumn(),
            'exits_today' => (int)$db->query("SELECT COUNT(*) FROM attendance_records WHERE DATE(recorded_at)=CURDATE() AND record_type='exit' AND is_void=0")->fetchColumn(),
            'late_today' => (int)$db->query("SELECT COUNT(*) FROM attendance_records WHERE DATE(recorded_at)=CURDATE() AND status='late' AND is_void=0")->fetchColumn(),
            'pending_requests' => (int)$db->query("SELECT COUNT(*) FROM requests WHERE status='pending'")->fetchColumn(),
        ];

        $recent = $db->query('SELECT ar.recorded_at, ar.record_type, ar.status, e.full_name FROM attendance_records ar JOIN employees e ON e.id=ar.employee_id ORDER BY ar.id DESC LIMIT 10')->fetchAll();
        Response::view('admin/dashboard', ['kpis' => $kpis, 'recent' => $recent]);
    }

    public function employees(): void
    {
        Auth::requireRole('admin');
        $employees = Database::connection()->query('SELECT e.*, a.name AS area_name FROM employees e LEFT JOIN areas a ON a.id=e.area_id ORDER BY e.id DESC')->fetchAll();
        Response::view('admin/employees', ['employees' => $employees]);
    }

    public function saveEmployee(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            flash('error', 'Token CSRF inválido');
            Response::redirect('/admin/employees');
        }

        $db = Database::connection();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'short_id' => trim($_POST['short_id'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'pin_hash' => password_hash(trim($_POST['pin'] ?? ''), PASSWORD_DEFAULT),
            'password_hash' => password_hash(trim($_POST['password'] ?? 'Cambio123!'), PASSWORD_DEFAULT),
            'area_id' => (int)($_POST['area_id'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id > 0) {
            $stmt = $db->prepare('UPDATE employees SET short_id=:short_id, full_name=:full_name, email=:email, area_id=:area_id, is_active=:is_active, updated_at=NOW() WHERE id=:id');
            $stmt->execute($data + ['id' => $id]);
        } else {
            $stmt = $db->prepare('INSERT INTO employees (short_id,full_name,email,pin_hash,password_hash,area_id,is_active,created_at,updated_at) VALUES (:short_id,:full_name,:email,:pin_hash,:password_hash,:area_id,:is_active,NOW(),NOW())');
            $stmt->execute($data);
        }

        Response::redirect('/admin/employees');
    }

    public function requests(): void
    {
        Auth::requireRole('admin');
        $rows = Database::connection()->query('SELECT r.*, e.full_name FROM requests r JOIN employees e ON e.id=r.employee_id ORDER BY r.id DESC')->fetchAll();
        Response::view('admin/requests', ['rows' => $rows]);
    }

    public function updateRequestStatus(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/requests');
        }

        $stmt = Database::connection()->prepare('UPDATE requests SET status=:status, admin_notes=:admin_notes, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'status' => $_POST['status'] ?? 'pending',
            'admin_notes' => trim($_POST['admin_notes'] ?? ''),
            'id' => (int)($_POST['id'] ?? 0),
        ]);
        Response::redirect('/admin/requests');
    }

    public function reports(): void
    {
        Auth::requireRole('admin');
        $rows = Database::connection()->query('SELECT ar.recorded_at,e.full_name,ar.record_type,ar.status,ar.origin FROM attendance_records ar JOIN employees e ON e.id=ar.employee_id ORDER BY ar.recorded_at DESC LIMIT 500')->fetchAll();
        Response::view('admin/reports', ['rows' => $rows]);
    }

    public function exportCsv(): void
    {
        Auth::requireRole('admin');
        $rows = Database::connection()->query('SELECT ar.recorded_at,e.short_id,e.full_name,ar.record_type,ar.status,ar.origin FROM attendance_records ar JOIN employees e ON e.id=ar.employee_id ORDER BY ar.recorded_at DESC')->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_asistencia.csv');
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['Fecha', 'ID corto', 'Empleado', 'Tipo', 'Estatus', 'Origen']);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
