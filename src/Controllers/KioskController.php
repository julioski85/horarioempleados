<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;

final class KioskController
{
    public function index(): void
    {
        Response::view('kiosk/index');
    }

    public function search(): void
    {
        $term = '%' . trim($_GET['q'] ?? '') . '%';
        $stmt = Database::connection()->prepare('SELECT id, short_id, full_name, base_photo_path, area_id FROM employees WHERE is_active=1 AND (full_name LIKE :q OR short_id LIKE :q) LIMIT 10');
        $stmt->execute(['q' => $term]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function nextAction(): void
    {
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $db = Database::connection();
        $lastStmt = $db->prepare('SELECT record_type FROM attendance_records WHERE employee_id=:id AND is_void=0 ORDER BY id DESC LIMIT 1');
        $lastStmt->execute(['id' => $employeeId]);
        $last = $lastStmt->fetchColumn();
        $next = $last === 'entry' ? 'exit' : 'entry';

        Response::json(['next_action' => $next]);
    }

    public function register(): void
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $employeeId = (int)($payload['employee_id'] ?? 0);
        $pin = trim((string)($payload['pin'] ?? ''));
        $selfie = (string)($payload['selfie'] ?? '');

        $db = Database::connection();
        $employeeStmt = $db->prepare('SELECT id,pin_hash FROM employees WHERE id=:id AND is_active=1');
        $employeeStmt->execute(['id' => $employeeId]);
        $employee = $employeeStmt->fetch();

        if (!$employee || !password_verify($pin, $employee['pin_hash'])) {
            Response::json(['ok' => false, 'message' => 'PIN inválido'], 422);
        }

        if (!preg_match('/^data:image\/(jpeg|png);base64,/', $selfie)) {
            Response::json(['ok' => false, 'message' => 'Selfie inválida'], 422);
        }

        $last = $db->prepare('SELECT record_type, recorded_at FROM attendance_records WHERE employee_id=:id AND is_void=0 ORDER BY id DESC LIMIT 1');
        $last->execute(['id' => $employeeId]);
        $lastRecord = $last->fetch();
        $nextAction = ($lastRecord && $lastRecord['record_type'] === 'entry') ? 'exit' : 'entry';

        $raw = explode(',', $selfie, 2)[1];
        $binary = base64_decode($raw, true);
        if ($binary === false || strlen($binary) > ((int)($_ENV['MAX_UPLOAD_MB'] ?? 5) * 1024 * 1024)) {
            Response::json(['ok' => false, 'message' => 'Tamaño de selfie no permitido'], 422);
        }

        $filename = sprintf('selfie_%d_%s.jpg', $employeeId, date('Ymd_His'));
        $path = dirname(__DIR__, 2) . '/public/assets/uploads/selfies/' . $filename;
        file_put_contents($path, $binary);

        $status = 'on_time';
        $stmt = $db->prepare('INSERT INTO attendance_records (employee_id, record_type, status, origin, device_name, recorded_at, selfie_path, is_void, created_at, updated_at) VALUES (:employee_id,:record_type,:status,"kiosk",:device,NOW(),:selfie,0,NOW(),NOW())');
        $stmt->execute([
            'employee_id' => $employeeId,
            'record_type' => $nextAction,
            'status' => $status,
            'device' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'kiosk', 0, 120),
            'selfie' => '/assets/uploads/selfies/' . $filename,
        ]);

        Response::json(['ok' => true, 'action' => $nextAction, 'message' => 'Registro guardado']);
    }
}
