<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\Response;
use App\Core\View;
use App\Services\AttendanceService;
use DateTimeImmutable;
use PDO;

final class KioskController
{
    private const ALLOWED_RECORD_TYPES = [
        AttendanceService::DB_RECORD_TYPE_ENTRY,
        AttendanceService::DB_RECORD_TYPE_EXIT,
    ];

    private const ALLOWED_DB_STATUSES = [
        AttendanceService::DB_STATUS_PENDING,
        AttendanceService::DB_STATUS_ENTRY_REGISTERED,
        AttendanceService::DB_STATUS_EXIT_REGISTERED,
        AttendanceService::DB_STATUS_LATE,
        AttendanceService::DB_STATUS_EARLY_EXIT,
        AttendanceService::DB_STATUS_INCOMPLETE,
        AttendanceService::DB_STATUS_ABSENCE,
        AttendanceService::DB_STATUS_MANUAL_INCIDENT,
        AttendanceService::DB_STATUS_VACATION,
        AttendanceService::DB_STATUS_REST_DAY,
    ];

    public function index(): void
    {
        View::render('kiosk/index', ['title' => 'Kiosco', 'show_floating_theme' => false], 'layouts/blank');
    }

    public function search(): void
    {
        $term = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($term) < 1) {
            Response::json(['employees' => []]);
        }

        $pdo = DB::pdo();
        $like = '%' . mb_strtolower($term) . '%';
        $st = $pdo->prepare('
            SELECT id, short_id, full_name, base_photo_path, is_active
            FROM employees
            WHERE LOWER(full_name) LIKE ?
               OR LOWER(short_id) LIKE ?
               OR CAST(id AS CHAR) = ?
            ORDER BY full_name ASC
            LIMIT 8
        ');
        $st->execute([$like, $like, $term]);
        $employees = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($employees as &$employee) {
            $employee['status'] = ((int) $employee['is_active'] === 1) ? 'Activo' : 'Inactivo';
            $employee['photo_path'] = $employee['base_photo_path'] ?: '/assets/uploads/base/avatar-base.svg';
        }
        unset($employee);

        Response::json(['employees' => $employees]);
    }

    public function nextAction(): void
    {
        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            Response::json(['ok' => false, 'message' => 'Empleado inválido']);
        }

        $pdo = DB::pdo();
        AttendanceService::ensureSchema($pdo);

        $settings = AttendanceService::loadSettings($pdo);
        $now = new DateTimeImmutable();
        $weeklySchedule = AttendanceService::loadEmployeeWeeklySchedule($pdo, $employeeId);
        $shift = AttendanceService::resolveCurrentShift($now, $weeklySchedule);

        $employeeSt = $pdo->prepare('SELECT id, full_name, base_photo_path, is_active FROM employees WHERE id = ? LIMIT 1');
        $employeeSt->execute([$employeeId]);
        $employee = $employeeSt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            Response::json(['ok' => false, 'message' => 'Empleado no encontrado']);
        }

        if ((int) $employee['is_active'] !== 1) {
            Response::json(['ok' => false, 'message' => 'El empleado está inactivo']);
        }

        $st = $pdo->prepare('SELECT record_type FROM attendance_records WHERE employee_id=? AND is_void=0 ORDER BY recorded_at DESC LIMIT 1');
        $st->execute([$employeeId]);
        $lastType = AttendanceService::normalizeRecordType((string) $st->fetchColumn());
        $nextRecordType = $lastType === AttendanceService::DB_RECORD_TYPE_ENTRY
            ? AttendanceService::DB_RECORD_TYPE_EXIT
            : AttendanceService::DB_RECORD_TYPE_ENTRY;
        $action = $nextRecordType === AttendanceService::DB_RECORD_TYPE_ENTRY ? 'entrada' : 'salida';

        if (!$shift) {
            Response::json([
                'ok' => true,
                'action' => $action,
                'record_type' => $nextRecordType,
                'employee' => [
                    'id' => (int) $employee['id'],
                    'full_name' => $employee['full_name'],
                    'photo_path' => $employee['base_photo_path'] ?: '/assets/uploads/base/avatar-base.svg',
                ],
                'shift' => null,
                'validation' => [
                    'allowed' => false,
                    'status' => 'sin_turno',
                    'db_status' => AttendanceService::DB_STATUS_REST_DAY,
                    'ui_message' => 'No hay turno aplicable para este empleado en este momento.',
                ],
            ]);
        }

        $validation = $nextRecordType === AttendanceService::DB_RECORD_TYPE_ENTRY
            ? AttendanceService::classifyEntry($now, $shift, $settings)
            : AttendanceService::classifyExit($now, $shift, $settings, null);

        Response::json([
            'ok' => true,
            'action' => $action,
            'record_type' => $nextRecordType,
            'employee' => [
                'id' => (int) $employee['id'],
                'full_name' => $employee['full_name'],
                'photo_path' => $employee['base_photo_path'] ?: '/assets/uploads/base/avatar-base.svg',
            ],
            'shift' => [
                'id' => (int) $shift['id'],
                'day_of_week' => (int) $shift['day_of_week'],
                'start_time' => $shift['start_time'],
                'end_time' => $shift['end_time'],
            ],
            'validation' => $validation,
        ]);
    }

    public function register(): void
    {
        try {
            $employeeId = (int) ($_POST['employee_id'] ?? 0);
            $pin = (string) ($_POST['pin'] ?? '');
            $selfieData = trim((string) ($_POST['selfie_data'] ?? ''));
            if ($employeeId <= 0) {
                Response::json(['ok' => false, 'message' => 'Empleado inválido']);
            }

            $pdo = DB::pdo();
            AttendanceService::ensureSchema($pdo);

            $st = $pdo->prepare('SELECT id,pin_hash,is_active FROM employees WHERE id=?');
            $st->execute([$employeeId]);
            $employee = $st->fetch(PDO::FETCH_ASSOC);

            if (!$employee || (int) $employee['is_active'] !== 1 || !password_verify($pin, (string) $employee['pin_hash'])) {
                Response::json(['ok' => false, 'message' => 'PIN incorrecto']);
            }
            if ($selfieData === '') {
                Response::json(['ok' => false, 'message' => 'La selfie es obligatoria']);
            }

            $last = $pdo->prepare('SELECT record_type FROM attendance_records WHERE employee_id=? AND is_void=0 ORDER BY recorded_at DESC LIMIT 1');
            $last->execute([$employeeId]);
            $lastType = AttendanceService::normalizeRecordType((string) $last->fetchColumn());
            $recordType = $lastType === AttendanceService::DB_RECORD_TYPE_ENTRY
                ? AttendanceService::DB_RECORD_TYPE_EXIT
                : AttendanceService::DB_RECORD_TYPE_ENTRY;

            $settings = AttendanceService::loadSettings($pdo);
            $now = new DateTimeImmutable();
            $weeklySchedule = AttendanceService::loadEmployeeWeeklySchedule($pdo, $employeeId);
            $shift = AttendanceService::resolveCurrentShift($now, $weeklySchedule);
            if (!$shift) {
                Response::json(['ok' => false, 'message' => 'No hay turno aplicable en este momento']);
            }

            $validation = $recordType === AttendanceService::DB_RECORD_TYPE_ENTRY
                ? AttendanceService::classifyEntry($now, $shift, $settings)
                : AttendanceService::classifyExit($now, $shift, $settings, $this->lastEntryAt($pdo, $employeeId));

            if (($validation['allowed'] ?? false) !== true) {
                Response::json(['ok' => false, 'message' => (string) ($validation['ui_message'] ?? 'Marca fuera de rango')]);
            }

            $selfiePath = $this->storeSelfie($selfieData, $employeeId);
            if ($selfiePath === null) {
                Response::json(['ok' => false, 'message' => 'No se pudo guardar la selfie']);
            }

            $dbStatus = AttendanceService::mapInternalStatusToDbStatus(
                (string) ($validation['status'] ?? ''),
                $recordType
            );
            if (isset($validation['db_status'])) {
                $dbStatus = AttendanceService::mapInternalStatusToDbStatus((string) $validation['db_status'], $recordType);
            }

            if (!in_array($recordType, self::ALLOWED_RECORD_TYPES, true)) {
                throw new \RuntimeException('record_type inválido para attendance_records: ' . $recordType);
            }
            if (!in_array($dbStatus, self::ALLOWED_DB_STATUSES, true)) {
                throw new \RuntimeException('status inválido para attendance_records: ' . $dbStatus);
            }

            $employeeScheduleShiftId = isset($shift['id']) ? (int) $shift['id'] : null;
            $legacyShiftId = $this->resolveLegacyShiftId($pdo, $shift);
            $params = [
                $employeeId,
                $legacyShiftId,
                $employeeScheduleShiftId,
                $recordType,
                $dbStatus,
                'kiosk',
                'web-kiosk',
                $selfiePath,
                $now->format('Y-m-d H:i:s'),
                0,
                null,
            ];

            $ins = $pdo->prepare(
                'INSERT INTO attendance_records(employee_id,shift_id,employee_schedule_shift_id,record_type,status,origin,device_name,selfie_path,recorded_at,is_void,void_reason) VALUES(?,?,?,?,?,?,?,?,?,?,?)'
            );
            try {
                $ins->execute($params);
            } catch (\PDOException $pdoException) {
                error_log('[kiosk/register] INSERT attendance_records failed. params=' . json_encode($params, JSON_UNESCAPED_UNICODE) . ' | message=' . $pdoException->getMessage());
                throw $pdoException;
            }

            Response::json([
                'ok' => true,
                'message' => (string) ($validation['ui_message'] ?? 'Asistencia registrada'),
                'record_type' => $recordType,
                'status' => $dbStatus,
            ]);
        } catch (\Throwable $exception) {
            $sqlErrorInfo = null;
            if ($exception instanceof \PDOException) {
                /** @var mixed $rawErrorInfo */
                $rawErrorInfo = $exception->errorInfo;
                $sqlErrorInfo = is_array($rawErrorInfo) ? json_encode($rawErrorInfo, JSON_UNESCAPED_UNICODE) : null;
            }
            error_log('[kiosk/register] SQL/PDO error: ' . $exception->getMessage() . ($sqlErrorInfo ? ' | errorInfo=' . $sqlErrorInfo : ''));
            Response::json([
                'ok' => false,
                'message' => 'No se pudo registrar la asistencia por un error de datos. Verifica el tipo de marca y el estado.',
            ]);
        }
    }

    private function storeSelfie(string $selfieData, int $employeeId): ?string
    {
        if (!preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#i', $selfieData, $matches)) {
            return null;
        }

        $format = strtolower($matches[1]);
        $extension = $format === 'jpeg' ? 'jpg' : $format;
        $binary = base64_decode(substr($selfieData, strpos($selfieData, ',') + 1), true);
        if ($binary === false || strlen($binary) < 512) {
            return null;
        }

        $relativeDir = '/assets/uploads/selfies/' . date('Y/m');
        $targetDir = dirname(__DIR__, 3) . $relativeDir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return null;
        }

        $name = sprintf('kiosk-e%s-%s.%s', $employeeId, bin2hex(random_bytes(6)), $extension);
        $fullPath = $targetDir . '/' . $name;
        if (file_put_contents($fullPath, $binary) === false) {
            return null;
        }

        return $relativeDir . '/' . $name;
    }

    private function lastEntryAt(PDO $pdo, int $employeeId): ?DateTimeImmutable
    {
        $st = $pdo->prepare(
            "SELECT recorded_at
            FROM attendance_records
            WHERE employee_id = ? AND is_void = 0 AND record_type IN ('entry', 'entrada')
            ORDER BY recorded_at DESC
            LIMIT 1"
        );
        $st->execute([$employeeId]);
        $value = $st->fetchColumn();
        if (!$value) {
            return null;
        }
        return new DateTimeImmutable((string) $value);
    }

    private function resolveLegacyShiftId(PDO $pdo, array $shift): ?int
    {
        $candidate = null;
        if (isset($shift['shift_id'])) {
            $candidate = (int) $shift['shift_id'];
        } elseif (isset($shift['legacy_shift_id'])) {
            $candidate = (int) $shift['legacy_shift_id'];
        }

        if ($candidate === null || $candidate <= 0) {
            return null;
        }

        $st = $pdo->prepare('SELECT id FROM shifts WHERE id = ? LIMIT 1');
        $st->execute([$candidate]);
        $exists = $st->fetchColumn();
        if ($exists === false) {
            return null;
        }

        return (int) $exists;
    }
}
