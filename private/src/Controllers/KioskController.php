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
        View::render('kiosk/index', ['title' => 'Kiosco'], 'layouts/blank');
    }

    public function search(): void
    {
        $term = trim((string) ($_GET['q'] ?? ''));
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT id,full_name,pin,status,photo_path FROM employees WHERE full_name LIKE ? OR id = ? LIMIT 1');
        $st->execute(["%{$term}%", (int) $term]);
        $employee = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        Response::json(['employee' => $employee]);
    }

    public function nextAction(): void
    {
        Response::json(['action' => 'entrada']);
    }

    public function register(): void
    {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $pin = (string) ($_POST['pin'] ?? '');
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT id,pin FROM employees WHERE id=?');
        $st->execute([$employeeId]);
        $employee = $st->fetch(PDO::FETCH_ASSOC);

        if (!$employee || $employee['pin'] !== $pin) {
            Response::json(['ok' => false, 'message' => 'PIN incorrecto']);
        }

        $ins = $pdo->prepare('INSERT INTO attendance(employee_id,event_type,created_at) VALUES(?,?,?)');
        $ins->execute([$employeeId, 'entrada', date('Y-m-d H:i:s')]);
        Response::json(['ok' => true, 'message' => 'Asistencia registrada']);
    }
}
