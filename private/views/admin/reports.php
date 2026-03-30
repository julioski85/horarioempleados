<section class="card table-card">
  <div class="card-head">
    <div>
      <h3>Reporte de asistencia</h3>
      <p class="card-subtitle">Exporta y revisa el histórico más reciente del equipo.</p>
    </div>
    <a href="<?= htmlspecialchars((string) ($export_url ?? (($base_path ?? '') . '/admin/reports/export-csv'))) ?>" class="btn btn-primary">Exportar CSV</a>
  </div>
  <form method="get" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/reports') ?>" class="report-filters-form">
    <label>
      Empleado
      <select name="employee_id">
        <option value="">Todos</option>
        <?php foreach (($employees ?? []) as $employee): ?>
          <option value="<?= (int) $employee['id'] ?>" <?= ((int) ($filters['employee_id'] ?? 0) === (int) $employee['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) $employee['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Desde
      <input type="date" name="date_from" value="<?= htmlspecialchars((string) ($filters['date_from'] ?? '')) ?>">
    </label>
    <label>
      Hasta
      <input type="date" name="date_to" value="<?= htmlspecialchars((string) ($filters['date_to'] ?? '')) ?>">
    </label>
    <div class="report-filters-actions">
      <button type="submit" class="btn">Aplicar filtros</button>
      <a href="<?= htmlspecialchars(($base_path ?? '') . '/admin/reports') ?>" class="btn">Limpiar</a>
    </div>
  </form>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Empleado</th><th>Evento</th><th>Puntualidad</th><th>Fecha</th></tr></thead>
      <tbody>
        <?php if (($rows ?? []) === []): ?>
          <tr><td colspan="4">No hay registros para los filtros seleccionados.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <?php
            $punctuality = (string) ($row['punctuality'] ?? '');
            if ($punctuality === 'con_retardo') {
                $badgeClass = 'warn';
                $badgeText = 'Con retardo';
            } elseif ($punctuality === 'incompleto') {
                $badgeClass = 'danger';
                $badgeText = 'Incompleto';
            } else {
                $badgeClass = 'ok';
                $badgeText = 'A tiempo';
            }
          ?>
          <tr>
            <td><?= htmlspecialchars((string) $row['full_name']) ?></td>
            <td><?= htmlspecialchars((string) $row['event_type']) ?></td>
            <td>
              <?php if (strtolower((string) ($row['event_type'] ?? '')) === 'entry'): ?>
                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
              <?php else: ?>
                <span class="record-meta">N/A</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars((string) $row['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
