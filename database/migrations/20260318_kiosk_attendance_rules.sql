-- Reglas globales de asistencia
CREATE TABLE IF NOT EXISTS attendance_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO attendance_settings(setting_key, setting_value) VALUES
('entry_early_minutes', '10'),
('entry_tolerance_minutes', '10'),
('entry_late_after_minutes', '10'),
('entry_max_late_minutes', '180'),
('min_minutes_between_in_out', '1'),
('allow_early_checkout', '0');

-- Horarios por empleado (soporta múltiples turnos por día)
CREATE TABLE IF NOT EXISTS employee_schedule_shifts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL COMMENT '1=Lunes ... 7=Domingo',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_employee_day (employee_id, day_of_week, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
