<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\Response;
use App\Core\View;
use PDO;

final class KioskController
{
    public function index(): void
    {
        View::render('kiosk/index', ['title' => 'Kiosco', 'show_floating_theme' => false], 'layouts/blank');
    }

    public function search(): void
    {
        $term = trim((string) ($_GET['q'] ?? ''));
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT id,short_id,full_name,base_photo_path,is_active FROM employees WHERE full_name LIKE ? OR short_id = ? OR id = ? LIMIT 1');
        $st->execute(["%{$term}%", $term, (int) $term]);
        $employee = $st->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($employee) {
            $employee['status'] = ((int) $employee['is_active'] === 1) ? 'Activo' : 'Inactivo';
            $employee['photo_path'] = $employee['base_photo_path'] ?: '/assets/uploads/base/avatar-base.svg';
        }

        Response::json(['employee' => $employee]);
    }

    public function nextAction(): void
    {
        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT record_type FROM attendance_records WHERE employee_id=? AND is_void=0 ORDER BY recorded_at DESC LIMIT 1');
        $st->execute([$employeeId]);
        $lastType = (string) $st->fetchColumn();

        Response::json(['action' => $lastType === 'entrada' ? 'salida' : 'entrada']);
    }

    public function register(): void
    {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $pin = (string) ($_POST['pin'] ?? '');
        $pdo = DB::pdo();

        $st = $pdo->prepare('SELECT id,pin_hash,is_active FROM employees WHERE id=?');
        $st->execute([$employeeId]);
        $employee = $st->fetch(PDO::FETCH_ASSOC);

        if (!$employee || (int) $employee['is_active'] !== 1 || !password_verify($pin, (string) $employee['pin_hash'])) {
            Response::json(['ok' => false, 'message' => 'PIN incorrecto']);
        }

        $last = $pdo->prepare('SELECT record_type FROM attendance_records WHERE employee_id=? AND is_void=0 ORDER BY recorded_at DESC LIMIT 1');
        $last->execute([$employeeId]);
        $recordType = ((string) $last->fetchColumn()) === 'entrada' ? 'salida' : 'entrada';

        $ins = $pdo->prepare('INSERT INTO attendance_records(employee_id,shift_id,record_type,status,origin,device_name,selfie_path,recorded_at,is_void,void_reason) VALUES(?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([$employeeId, null, $recordType, 'confirmado', 'kiosk', 'web-kiosk', null, date('Y-m-d H:i:s'), 0, null]);

        Response::json(['ok' => true, 'message' => 'Asistencia registrada']);
    }
}
