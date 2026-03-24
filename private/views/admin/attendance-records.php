<section class="card table-card">
  <div class="card-head">
    <div>
      <h3>Registros de asistencia</h3>
      <p class="card-subtitle">Consulta, anula y restaura marcas con trazabilidad completa.</p>
    </div>
    <form method="get" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/attendance-records') ?>" class="inline-form records-filter-form">
      <select name="filter" onchange="this.form.submit()">
        <option value="active" <?= ($filter ?? 'active') === 'active' ? 'selected' : '' ?>>Solo activos</option>
        <option value="void" <?= ($filter ?? '') === 'void' ? 'selected' : '' ?>>Solo anulados</option>
        <option value="all" <?= ($filter ?? '') === 'all' ? 'selected' : '' ?>>Todos</option>
      </select>
      <button type="submit" class="btn">Aplicar</button>
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Empleado</th>
          <th>Tipo</th>
          <th>Fecha</th>
          <th>Estatus</th>
          <th>Trazabilidad</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (($rows ?? []) === []): ?>
          <tr><td colspan="7">No hay registros para el filtro seleccionado.</td></tr>
        <?php endif; ?>
        <?php foreach (($rows ?? []) as $row): ?>
          <?php $isVoid = ((int) ($row['is_void'] ?? 0)) === 1; ?>
          <tr class="<?= $isVoid ? 'void-row' : '' ?>">
            <td>#<?= (int) $row['id'] ?></td>
            <td>
              <strong><?= htmlspecialchars($row['full_name'] ?? '') ?></strong>
              <small class="record-meta">Empleado ID: <?= (int) ($row['employee_id'] ?? 0) ?></small>
            </td>
            <td><?= htmlspecialchars(ucfirst((string) ($row['record_type'] ?? ''))) ?></td>
            <td><?= htmlspecialchars((string) ($row['recorded_at'] ?? '')) ?></td>
            <td>
              <?php if ($isVoid): ?>
                <span class="badge danger">Anulado</span>
              <?php else: ?>
                <span class="badge ok">Activo</span>
              <?php endif; ?>
              <small class="record-meta">Estado técnico: <?= htmlspecialchars((string) ($row['status'] ?? '')) ?></small>
            </td>
            <td>
              <?php if ($isVoid): ?>
                <small class="record-meta">
                  Motivo: <?= htmlspecialchars((string) ($row['void_reason'] ?? 'Sin motivo')) ?><br>
                  Por: <?= htmlspecialchars((string) ($row['voided_by_name'] ?? 'Usuario sin nombre')) ?><br>
                  Fecha: <?= htmlspecialchars((string) ($row['voided_at'] ?? 'N/D')) ?>
                </small>
              <?php else: ?>
                <small class="record-meta">Origen: <?= htmlspecialchars((string) ($row['origin'] ?? 'N/D')) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!$isVoid): ?>
                <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/attendance-records/void') ?>" class="record-action-form" onsubmit="return confirm('¿Seguro que deseas anular este registro? Esta acción no borra el historial.');">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                  <input type="hidden" name="record_id" value="<?= (int) $row['id'] ?>">
                  <input type="text" name="void_reason" placeholder="Motivo obligatorio" required maxlength="255">
                  <button type="submit" class="btn btn-danger">Anular</button>
                </form>
              <?php else: ?>
                <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/attendance-records/restore') ?>" onsubmit="return confirm('¿Deseas restaurar este registro anulado?');">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($csrf ?? '')) ?>">
                  <input type="hidden" name="record_id" value="<?= (int) $row['id'] ?>">
                  <button type="submit" class="btn">Restaurar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
