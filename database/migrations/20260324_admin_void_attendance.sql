-- Auditoría para anulación/restauración lógica de registros de asistencia
ALTER TABLE attendance_records
  ADD COLUMN IF NOT EXISTS voided_by_user_id BIGINT UNSIGNED NULL AFTER void_reason,
  ADD COLUMN IF NOT EXISTS voided_at DATETIME NULL AFTER voided_by_user_id;
