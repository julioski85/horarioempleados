CREATE DATABASE IF NOT EXISTS u801126150_equipo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE u801126150_equipo;

CREATE TABLE admins (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE teams (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE areas (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  team_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_areas_team FOREIGN KEY (team_id) REFERENCES teams(id)
);

CREATE TABLE employees (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  short_id VARCHAR(20) NOT NULL UNIQUE,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  pin_hash VARCHAR(255) NOT NULL,
  area_id BIGINT UNSIGNED NULL,
  team_id BIGINT UNSIGNED NULL,
  base_photo_path VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_employees_area (area_id),
  CONSTRAINT fk_employees_area FOREIGN KEY (area_id) REFERENCES areas(id),
  CONSTRAINT fk_employees_team FOREIGN KEY (team_id) REFERENCES teams(id)
);

CREATE TABLE employee_photos (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  is_base TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_employee_photos_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE schedules (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  weekday TINYINT UNSIGNED NOT NULL COMMENT '1=Lunes ... 7=Domingo',
  is_rest_day TINYINT(1) NOT NULL DEFAULT 0,
  is_off_day TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_schedule_employee_day (employee_id, weekday),
  CONSTRAINT fk_schedules_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE shifts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  schedule_id BIGINT UNSIGNED NOT NULL,
  shift_order TINYINT UNSIGNED NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_shift_schedule_order (schedule_id, shift_order),
  CONSTRAINT fk_shifts_schedule FOREIGN KEY (schedule_id) REFERENCES schedules(id)
);

CREATE TABLE attendance_records (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  shift_id BIGINT UNSIGNED NULL,
  record_type ENUM('entry','exit') NOT NULL,
  status ENUM('pending','entry_registered','exit_registered','late','early_exit','incomplete','absence','manual_incident','vacation','rest_day') DEFAULT 'pending',
  origin ENUM('kiosk','admin_manual','employee_panel','system') NOT NULL DEFAULT 'kiosk',
  device_name VARCHAR(160) NULL,
  selfie_path VARCHAR(255) NULL,
  recorded_at DATETIME NOT NULL,
  is_void TINYINT(1) NOT NULL DEFAULT 0,
  void_reason VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_attendance_employee_date (employee_id, recorded_at),
  INDEX idx_attendance_status (status),
  CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
  CONSTRAINT fk_attendance_shift FOREIGN KEY (shift_id) REFERENCES shifts(id)
);

CREATE TABLE attendance_selfies (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  attendance_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_size INT UNSIGNED NULL,
  mime_type VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_attendance_selfie_attendance FOREIGN KEY (attendance_id) REFERENCES attendance_records(id)
);

CREATE TABLE requests (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  request_type ENUM('vacation','permission','rest') NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_requests_status (status),
  CONSTRAINT fk_requests_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE incidents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  attendance_id BIGINT UNSIGNED NULL,
  incident_type ENUM('manual_adjustment','late','absence','incomplete_shift') NOT NULL,
  description TEXT NOT NULL,
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_incidents_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
  CONSTRAINT fk_incidents_attendance FOREIGN KEY (attendance_id) REFERENCES attendance_records(id),
  CONSTRAINT fk_incidents_admin FOREIGN KEY (created_by_admin_id) REFERENCES admins(id)
);

CREATE TABLE system_settings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE report_cache (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  report_key VARCHAR(120) NOT NULL,
  parameters_json JSON NOT NULL,
  generated_at DATETIME NOT NULL,
  expires_at DATETIME NULL,
  data_json LONGTEXT NOT NULL,
  INDEX idx_report_cache_key (report_key)
);

CREATE TABLE user_sessions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_role ENUM('admin','employee') NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  session_token CHAR(64) NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  last_activity DATETIME NOT NULL,
  created_at TIMESTAMP NULL,
  INDEX idx_user_sessions_token (session_token)
);

CREATE TABLE audit_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  actor_id BIGINT UNSIGNED NULL,
  actor_role ENUM('admin','employee','system') NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  old_data JSON NULL,
  new_data JSON NULL,
  reason VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_created_at (created_at)
);

INSERT INTO teams (name, created_at, updated_at) VALUES ('Operaciones', NOW(), NOW());
INSERT INTO areas (team_id, name, created_at, updated_at) VALUES (1, 'Gimnasio Principal', NOW(), NOW());
INSERT INTO admins (name, email, password_hash, is_active, created_at, updated_at)
VALUES ('Admin Inicial', 'admin@gym.local', '$2y$10$7vhfJ0yyVxYVfwHzfwFx1OT6jgvPwWf1oIctM4vgMkeBW.uQfCb.O', 1, NOW(), NOW());
INSERT INTO employees (short_id, full_name, email, password_hash, pin_hash, area_id, team_id, is_active, created_at, updated_at) VALUES
('E001', 'Ana Torres', 'ana@gym.local', '$2y$10$JGiYtv2icclQYQE7Wq9iM.R73ek4qOlMqGQ9JGQf9fGxYxR5jRYw.', '$2y$10$jVDoSx96VkCCDeSm9dGQx.aYf0fQg2v72RkhxNhToN8M6Qe4Vh1kC', 1, 1, 1, NOW(), NOW()),
('E002', 'Luis Méndez', 'luis@gym.local', '$2y$10$JGiYtv2icclQYQE7Wq9iM.R73ek4qOlMqGQ9JGQf9fGxYxR5jRYw.', '$2y$10$jVDoSx96VkCCDeSm9dGQx.aYf0fQg2v72RkhxNhToN8M6Qe4Vh1kC', 1, 1, 1, NOW(), NOW());

INSERT INTO system_settings (setting_key, setting_value, description, updated_at) VALUES
('minutes_early_entry', '15', 'Minutos antes permitidos para entrada', NOW()),
('minutes_late_tolerance', '10', 'Tolerancia de retardo', NOW()),
('minutes_max_late_entry', '45', 'Máximo para entrada tardía', NOW()),
('minutes_min_shift_duration', '60', 'Tiempo mínimo entre entrada y salida', NOW()),
('allow_early_exit', '0', 'Permite salida anticipada', NOW()),
('incomplete_shift_margin', '30', 'Margen turno incompleto', NOW());
