<section class="card">
  <div class="card-head">
    <div>
      <h3>Reglas globales de asistencia</h3>
      <p class="card-subtitle">Estas reglas se aplican al kiosco para validar puntualidad, retardo y bloqueos.</p>
    </div>
    <span class="badge">Global</span>
  </div>

  <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/attendance/save') ?>" class="form-grid">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <label>Minutos antes permitidos para entrada
      <input type="number" min="0" name="entry_early_minutes" value="<?= (int) ($settings['entry_early_minutes'] ?? 10) ?>" required>
    </label>
    <label>Minutos de tolerancia puntual
      <input type="number" min="0" name="entry_tolerance_minutes" value="<?= (int) ($settings['entry_tolerance_minutes'] ?? 10) ?>" required>
    </label>
    <label>Minutos a partir de los cuales se considera retardo
      <input type="number" min="0" name="entry_late_after_minutes" value="<?= (int) ($settings['entry_late_after_minutes'] ?? 10) ?>" required>
    </label>
    <label>Máximo de minutos para permitir entrada tardía (0 = sin límite)
      <input type="number" min="0" name="entry_max_late_minutes" value="<?= (int) ($settings['entry_max_late_minutes'] ?? 180) ?>" required>
    </label>
    <label>Tiempo mínimo entre entrada y salida (minutos)
      <input type="number" min="0" name="min_minutes_between_in_out" value="<?= (int) ($settings['min_minutes_between_in_out'] ?? 1) ?>" required>
    </label>
    <label>Permitir salida anticipada
      <select name="allow_early_checkout">
        <option value="0" <?= ((int) ($settings['allow_early_checkout'] ?? 0) === 0) ? 'selected' : '' ?>>No</option>
        <option value="1" <?= ((int) ($settings['allow_early_checkout'] ?? 0) === 1) ? 'selected' : '' ?>>Sí</option>
      </select>
    </label>
    <button class="btn btn-primary" type="submit">Guardar reglas</button>
  </form>
</section>
