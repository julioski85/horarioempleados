<?php

declare(strict_types=1);

namespace App\Services;

use DateInterval;
use DateTimeImmutable;
use PDO;

final class AttendanceService
{
    public const DB_RECORD_TYPE_ENTRY = 'entry';
    public const DB_RECORD_TYPE_EXIT = 'exit';
    public const DB_STATUS_PENDING = 'pending';
    public const DB_STATUS_ENTRY_REGISTERED = 'entry_registered';
    public const DB_STATUS_EXIT_REGISTERED = 'exit_registered';
    public const DB_STATUS_LATE = 'late';
    public const DB_STATUS_EARLY_EXIT = 'early_exit';
    public const DB_STATUS_INCOMPLETE = 'incomplete';
    public const DB_STATUS_ABSENCE = 'absence';
    public const DB_STATUS_MANUAL_INCIDENT = 'manual_incident';
    public const DB_STATUS_VACATION = 'vacation';
    public const DB_STATUS_REST_DAY = 'rest_day';

    public const DEFAULT_SETTINGS = [
        'entry_early_minutes' => 10,
        'entry_tolerance_minutes' => 10,
        'entry_late_after_minutes' => 10,
        'entry_max_late_minutes' => 180,
        'min_minutes_between_in_out' => 1,
        'allow_early_checkout' => 0,
    ];

    public static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS attendance_settings (
                setting_key VARCHAR(80) PRIMARY KEY,
                setting_value VARCHAR(255) NOT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS employee_schedule_shifts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                employee_id BIGINT UNSIGNED NOT NULL,
                day_of_week TINYINT UNSIGNED NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_employee_day (employee_id, day_of_week, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $ins = $pdo->prepare('INSERT IGNORE INTO attendance_settings(setting_key, setting_value) VALUES (?,?)');
        foreach (self::DEFAULT_SETTINGS as $key => $value) {
            $ins->execute([$key, (string) $value]);
        }

        self::ensureAttendanceRecordsColumns($pdo);
    }

    public static function loadSettings(PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $settings = self::DEFAULT_SETTINGS;
        $rows = $pdo->query('SELECT setting_key, setting_value FROM attendance_settings')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if (array_key_exists($row['setting_key'], $settings)) {
                $settings[$row['setting_key']] = (int) $row['setting_value'];
            }
        }
        return $settings;
    }

    public static function loadEmployeeWeeklySchedule(PDO $pdo, int $employeeId): array
    {
        self::ensureSchema($pdo);
        $st = $pdo->prepare(
            'SELECT id, employee_id, day_of_week, start_time, end_time
            FROM employee_schedule_shifts
            WHERE employee_id = ? AND is_active = 1
            ORDER BY day_of_week ASC, start_time ASC'
        );
        $st->execute([$employeeId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function classifyEntry(DateTimeImmutable $now, array $shift, array $settings): array
    {
        $shiftStart = self::dateAt($now, (string) $shift['start_time']);
        $earlyWindowStart = $shiftStart->sub(new DateInterval('PT' . max(0, (int) $settings['entry_early_minutes']) . 'M'));
        $punctualUntil = $shiftStart->add(new DateInterval('PT' . max(0, (int) $settings['entry_tolerance_minutes']) . 'M'));
        $lateAfter = $shiftStart->add(new DateInterval('PT' . max(0, (int) $settings['entry_late_after_minutes']) . 'M'));
        $maxLateMinutes = max(0, (int) $settings['entry_max_late_minutes']);
        $latestAllowed = $shiftStart->add(new DateInterval('PT' . $maxLateMinutes . 'M'));

        if ($now < $earlyWindowStart) {
            return [
                'allowed' => false,
                'status' => 'fuera_de_rango',
                'db_status' => self::DB_STATUS_PENDING,
                'ui_message' => 'Aún no puedes registrar entrada. Llegaste antes de la ventana permitida.',
                'window_start' => $earlyWindowStart->format('H:i'),
            ];
        }

        if ($maxLateMinutes > 0 && $now > $latestAllowed) {
            return [
                'allowed' => false,
                'status' => 'fuera_de_rango',
                'db_status' => self::DB_STATUS_PENDING,
                'ui_message' => 'Se excedió el máximo permitido para entrada tardía.',
                'window_start' => $earlyWindowStart->format('H:i'),
            ];
        }

        if ($now <= $punctualUntil) {
            $internalStatus = $now <= $shiftStart ? 'a_tiempo' : 'en_tolerancia';
            return [
                'allowed' => true,
                'status' => $internalStatus,
                'db_status' => self::mapInternalStatusToDbStatus($internalStatus, self::DB_RECORD_TYPE_ENTRY),
                'ui_message' => $now <= $shiftStart ? 'Entrada a tiempo.' : 'Entrada válida dentro de tolerancia.',
                'window_start' => $earlyWindowStart->format('H:i'),
            ];
        }

        if ($now > $lateAfter) {
            return [
                'allowed' => true,
                'status' => 'retardo',
                'db_status' => self::mapInternalStatusToDbStatus('retardo', self::DB_RECORD_TYPE_ENTRY),
                'ui_message' => 'Entrada registrada con retardo.',
                'window_start' => $earlyWindowStart->format('H:i'),
            ];
        }

        return [
            'allowed' => true,
            'status' => 'en_tolerancia',
            'db_status' => self::mapInternalStatusToDbStatus('en_tolerancia', self::DB_RECORD_TYPE_ENTRY),
            'ui_message' => 'Entrada válida dentro de tolerancia.',
            'window_start' => $earlyWindowStart->format('H:i'),
        ];
    }

    public static function classifyExit(DateTimeImmutable $now, array $shift, array $settings, ?DateTimeImmutable $lastEntry): array
    {
        $shiftEnd = self::dateAt($now, (string) $shift['end_time']);
        if ($lastEntry !== null) {
            $minMinutes = max(0, (int) $settings['min_minutes_between_in_out']);
            $allowedAfter = $lastEntry->add(new DateInterval('PT' . $minMinutes . 'M'));
            if ($now < $allowedAfter) {
                return [
                    'allowed' => false,
                    'status' => 'fuera_de_rango',
                    'db_status' => self::DB_STATUS_PENDING,
                    'ui_message' => 'Aún no puedes registrar salida. Debe pasar el tiempo mínimo desde la entrada.',
                ];
            }
        }

        if ((int) $settings['allow_early_checkout'] !== 1 && $now < $shiftEnd) {
            return [
                'allowed' => false,
                'status' => 'fuera_de_rango',
                'db_status' => self::DB_STATUS_PENDING,
                'ui_message' => 'La salida anticipada no está permitida para este turno.',
            ];
        }

        return [
            'allowed' => true,
            'status' => 'confirmado',
            'db_status' => self::mapInternalStatusToDbStatus('confirmado', self::DB_RECORD_TYPE_EXIT),
            'ui_message' => 'Salida registrada correctamente.',
        ];
    }

    public static function mapInternalStatusToDbStatus(string $internalStatus, string $recordType): string
    {
        $normalizedType = self::normalizeRecordType($recordType);
        $map = [
            'a_tiempo' => self::DB_STATUS_ENTRY_REGISTERED,
            'en_tolerancia' => self::DB_STATUS_ENTRY_REGISTERED,
            'retardo' => self::DB_STATUS_LATE,
            'confirmado' => $normalizedType === self::DB_RECORD_TYPE_EXIT ? self::DB_STATUS_EXIT_REGISTERED : self::DB_STATUS_ENTRY_REGISTERED,
            'fuera_de_rango' => self::DB_STATUS_PENDING,
            'sin_turno' => self::DB_STATUS_REST_DAY,
        ];

        if (isset($map[$internalStatus])) {
            return $map[$internalStatus];
        }

        $validDbStatuses = [
            self::DB_STATUS_PENDING,
            self::DB_STATUS_ENTRY_REGISTERED,
            self::DB_STATUS_EXIT_REGISTERED,
            self::DB_STATUS_LATE,
            self::DB_STATUS_EARLY_EXIT,
            self::DB_STATUS_INCOMPLETE,
            self::DB_STATUS_ABSENCE,
            self::DB_STATUS_MANUAL_INCIDENT,
            self::DB_STATUS_VACATION,
            self::DB_STATUS_REST_DAY,
        ];

        return in_array($internalStatus, $validDbStatuses, true) ? $internalStatus : self::DB_STATUS_PENDING;
    }

    public static function normalizeRecordType(?string $recordType): ?string
    {
        $value = mb_strtolower(trim((string) $recordType));
        if ($value === '') {
            return null;
        }

        return match ($value) {
            'entry', 'entrada' => self::DB_RECORD_TYPE_ENTRY,
            'exit', 'salida' => self::DB_RECORD_TYPE_EXIT,
            default => null,
        };
    }

    public static function resolveCurrentShift(DateTimeImmutable $now, array $weeklyShifts): ?array
    {
        $weekday = (int) $now->format('N');
        $todayShifts = array_values(array_filter($weeklyShifts, static fn(array $s): bool => (int) $s['day_of_week'] === $weekday));
        if ($todayShifts === []) {
            return null;
        }

        $candidate = null;
        $bestDiff = PHP_INT_MAX;
        foreach ($todayShifts as $shift) {
            $start = self::dateAt($now, (string) $shift['start_time']);
            $diff = abs($now->getTimestamp() - $start->getTimestamp());
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $candidate = $shift;
            }
        }

        return $candidate;
    }

    private static function dateAt(DateTimeImmutable $reference, string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($reference->format('Y-m-d') . ' ' . $time, $reference->getTimezone());
    }

    private static function ensureAttendanceRecordsColumns(PDO $pdo): void
    {
        $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        if ($dbName === '') {
            return;
        }

        $requiredColumns = [
            'shift_id' => 'BIGINT UNSIGNED NULL',
            'status' => "VARCHAR(40) NOT NULL DEFAULT 'pending'",
            'origin' => "VARCHAR(40) NOT NULL DEFAULT 'kiosk'",
            'device_name' => 'VARCHAR(120) NULL',
            'selfie_path' => 'VARCHAR(255) NULL',
            'is_void' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'void_reason' => 'VARCHAR(255) NULL',
        ];

        $columnExistsSt = $pdo->prepare(
            'SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1'
        );

        foreach ($requiredColumns as $column => $definition) {
            $columnExistsSt->execute([$dbName, 'attendance_records', $column]);
            if ($columnExistsSt->fetchColumn()) {
                continue;
            }

            $pdo->exec("ALTER TABLE attendance_records ADD COLUMN {$column} {$definition}");
        }
    }
}
