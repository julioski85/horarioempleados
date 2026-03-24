<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Response;
use App\Core\View;
use App\Services\AttendanceService;
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
        AttendanceService::ensureSchema($pdo);
        $employees = $pdo->query('SELECT * FROM employees ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        $shifts = $pdo->query(
            'SELECT employee_id, day_of_week, start_time, end_time
            FROM employee_schedule_shifts
            WHERE is_active = 1
            ORDER BY employee_id ASC, day_of_week ASC, start_time ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $scheduleByEmployee = [];
        foreach ($shifts as $shift) {
            $employeeId = (int) $shift['employee_id'];
            $day = (int) $shift['day_of_week'];
            $scheduleByEmployee[$employeeId] ??= [];
            $scheduleByEmployee[$employeeId][$day] ??= [];
            $scheduleByEmployee[$employeeId][$day][] = $shift['start_time'] . '-' . $shift['end_time'];
        }
        View::render('admin/employees', [
            'employees' => $employees,
            'schedule_by_employee' => $scheduleByEmployee,
            'csrf' => Csrf::token(),
            'title' => 'Empleados',
        ]);
    }

    public function attendanceSettings(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();
        $settings = AttendanceService::loadSettings($pdo);
        View::render('admin/attendance', [
            'settings' => $settings,
            'csrf' => Csrf::token(),
            'title' => 'Reglas de asistencia',
        ]);
    }

    public function saveAttendanceSettings(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/attendance');
        }
        $pdo = DB::pdo();
        AttendanceService::ensureSchema($pdo);
        $allowedKeys = array_keys(AttendanceService::DEFAULT_SETTINGS);
        $upsert = $pdo->prepare(
            'INSERT INTO attendance_settings(setting_key, setting_value, updated_at) VALUES(?,?,NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        foreach ($allowedKeys as $key) {
            $value = max(0, (int) ($_POST[$key] ?? AttendanceService::DEFAULT_SETTINGS[$key]));
            $upsert->execute([$key, (string) $value]);
        }
        Response::redirect('/admin/attendance');
    }

    public function saveEmployeeSchedule(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/employees');
        }

        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            Response::redirect('/admin/employees');
        }

        $pdo = DB::pdo();
        AttendanceService::ensureSchema($pdo);

        $entries = $this->collectScheduleEntries($_POST, $employeeId);
        $this->saveEmployeeScheduleEntries($pdo, $employeeId, $entries);

        Response::redirect('/admin/employees');
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

        $photoPath = $this->storeEmployeePhoto($_FILES['photo'] ?? null);

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
            $photoPath,
            $isActive,
        ]);
        $employeeId = (int) $pdo->lastInsertId();
        AttendanceService::ensureSchema($pdo);
        $entries = $this->collectScheduleEntries($_POST, $employeeId);
        $this->saveEmployeeScheduleEntries($pdo, $employeeId, $entries);
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

        AttendanceService::ensureSchema($pdo);
        $entries = $this->collectScheduleEntries($_POST, $id);
        $this->saveEmployeeScheduleEntries($pdo, $id, $entries);

        Response::redirect('/admin/employees');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array{0:int,1:int,2:string,3:string}>
     */
    private function collectScheduleEntries(array $payload, int $employeeId): array
    {
        $days = [1, 2, 3, 4, 5, 6, 7];
        $entries = [];
        foreach ($days as $day) {
            $startInput = trim((string) ($payload['day_' . $day . '_start'] ?? ''));
            $endInput = trim((string) ($payload['day_' . $day . '_end'] ?? ''));
            if ($startInput !== '' || $endInput !== '') {
                if (
                    preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $startInput, $startMatch)
                    && preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $endInput, $endMatch)
                ) {
                    $start = sprintf('%02d:%02d:00', (int) $startMatch[1], (int) $startMatch[2]);
                    $end = sprintf('%02d:%02d:00', (int) $endMatch[1], (int) $endMatch[2]);
                    if ($start < $end) {
                        $entries[] = [$employeeId, $day, $start, $end];
                    }
                }
            }

            $raw = trim((string) ($payload['day_' . $day] ?? ''));
            if ($raw === '') {
                continue;
            }

            $ranges = array_filter(array_map('trim', explode(',', $raw)), static fn(string $v): bool => $v !== '');
            foreach ($ranges as $range) {
                if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)\s*-\s*([01]?\d|2[0-3]):([0-5]\d)$/', $range, $m)) {
                    continue;
                }
                $start = sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
                $end = sprintf('%02d:%02d:00', (int) $m[3], (int) $m[4]);
                if ($start >= $end) {
                    continue;
                }
                $duplicate = false;
                foreach ($entries as $entry) {
                    if ($entry[1] === $day && $entry[2] === $start && $entry[3] === $end) {
                        $duplicate = true;
                        break;
                    }
                }
                if (!$duplicate) {
                    $entries[] = [$employeeId, $day, $start, $end];
                }
            }
        }

        return $entries;
    }

    /**
     * @param array<int, array{0:int,1:int,2:string,3:string}> $entries
     */
    private function saveEmployeeScheduleEntries(PDO $pdo, int $employeeId, array $entries): void
    {
        $pdo->beginTransaction();
        $disable = $pdo->prepare('UPDATE employee_schedule_shifts SET is_active = 0 WHERE employee_id = ?');
        $disable->execute([$employeeId]);
        if ($entries !== []) {
            $insert = $pdo->prepare(
                'INSERT INTO employee_schedule_shifts(employee_id, day_of_week, start_time, end_time, is_active, created_at, updated_at)
                VALUES(?,?,?,?,1,NOW(),NOW())'
            );
            foreach ($entries as $entry) {
                $insert->execute($entry);
            }
        }
        $pdo->commit();
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

    public function attendanceRecords(): void
    {
        Auth::requireRole('admin');
        $pdo = DB::pdo();
        AttendanceService::ensureSchema($pdo);

        $filter = (string) ($_GET['filter'] ?? 'active');
        if (!in_array($filter, ['active', 'void', 'all'], true)) {
            $filter = 'active';
        }

        $where = '';
        if ($filter === 'active') {
            $where = 'WHERE ar.is_void = 0';
        } elseif ($filter === 'void') {
            $where = 'WHERE ar.is_void = 1';
        }

        $rows = $pdo->query(
            "SELECT
                ar.id,
                ar.employee_id,
                e.full_name,
                ar.record_type,
                ar.status,
                ar.origin,
                ar.recorded_at,
                ar.is_void,
                ar.void_reason,
                ar.voided_at,
                ar.voided_by_user_id,
                u.full_name AS voided_by_name
            FROM attendance_records ar
            JOIN employees e ON e.id = ar.employee_id
            LEFT JOIN employees u ON u.id = ar.voided_by_user_id
            {$where}
            ORDER BY ar.recorded_at DESC
            LIMIT 200"
        )->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/attendance-records', [
            'rows' => $rows,
            'filter' => $filter,
            'csrf' => Csrf::token(),
            'title' => 'Registros de asistencia',
        ]);
    }

    public function voidAttendanceRecord(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/attendance-records');
        }

        $recordId = (int) ($_POST['record_id'] ?? 0);
        $reason = trim((string) ($_POST['void_reason'] ?? ''));
        if ($recordId <= 0 || $reason === '') {
            Response::redirect('/admin/attendance-records?filter=all');
        }

        $actor = Auth::user();
        $actorEmployeeId = (int) ($actor['id'] ?? 0);

        $pdo = DB::pdo();
        AttendanceService::ensureSchema($pdo);
        $st = $pdo->prepare(
            'UPDATE attendance_records
            SET is_void = 1,
                void_reason = ?,
                voided_by_user_id = ?,
                voided_at = NOW()
            WHERE id = ?
              AND is_void = 0'
        );
        $st->execute([$reason, $actorEmployeeId > 0 ? $actorEmployeeId : null, $recordId]);

        Response::redirect('/admin/attendance-records?filter=all');
    }

    public function restoreAttendanceRecord(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Response::redirect('/admin/attendance-records');
        }

        $recordId = (int) ($_POST['record_id'] ?? 0);
        if ($recordId <= 0) {
            Response::redirect('/admin/attendance-records?filter=all');
        }

        $pdo = DB::pdo();
        AttendanceService::ensureSchema($pdo);
        $st = $pdo->prepare(
            'UPDATE attendance_records
            SET is_void = 0,
                void_reason = NULL,
                voided_by_user_id = NULL,
                voided_at = NULL
            WHERE id = ?
              AND is_void = 1'
        );
        $st->execute([$recordId]);

        Response::redirect('/admin/attendance-records?filter=all');
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

    private function storeEmployeePhoto(mixed $photo): string
    {
        if (!is_array($photo) || !isset($photo['error']) || (int) $photo['error'] !== UPLOAD_ERR_OK) {
            return '/assets/uploads/base/avatar-base.svg';
        }

        $tmpName = (string) ($photo['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return '/assets/uploads/base/avatar-base.svg';
        }

        $mime = mime_content_type($tmpName) ?: '';
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            return '/assets/uploads/base/avatar-base.svg';
        }

        $fileName = 'employee-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];
        $relativeDir = '/assets/uploads/employees';
        $targetDir = dirname(__DIR__, 3) . $relativeDir;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $targetPath = $targetDir . '/' . $fileName;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            return '/assets/uploads/base/avatar-base.svg';
        }

        return $relativeDir . '/' . $fileName;
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
