<div class="split-grid">
  <section class="card form-card">
    <div class="card-head">
      <div>
        <h3>Nuevo empleado</h3>
        <p class="card-subtitle">Alta rápida y segura para nuevos integrantes.</p>
      </div>
      <span class="badge">Alta</span>
    </div>
    <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/employees/save') ?>" class="form-grid" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label>ID corto<input name="short_id" maxlength="32" placeholder="EMP-0001"></label>
      <label>Nombre completo<input name="full_name" required></label>
      <label>Email<input type="email" name="email" required></label>
      <label>PIN kiosco<input name="pin" maxlength="6" required></label>
      <label>Estatus<select name="status"><option>Activo</option><option>Inactivo</option></select></label>
      <label>Foto del empleado
        <input type="file" name="photo" accept="image/*" capture="user" required>
      </label>
      <button class="btn btn-primary" type="submit">Guardar empleado</button>
    </form>
  </section>

  <section class="card table-card">
    <div class="card-head">
      <div>
        <h3>Gestión de empleados</h3>
        <p class="card-subtitle">Listado completo con edición inline.</p>
      </div>
      <span class="badge"><?= count($employees) ?> registros</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID corto</th><th>Empleado</th><th>Email</th><th>Horario</th><th>Estatus</th><th>Edición</th></tr></thead>
        <tbody>
        <?php foreach ($employees as $e): ?>
        <?php $employeeSchedule = $schedule_by_employee[(int) $e['id']] ?? []; ?>
        <tr>
          <td><?= htmlspecialchars((string) ($e['short_id'] ?? '')) ?></td>
          <td><?= htmlspecialchars($e['full_name']) ?></td>
          <td><?= htmlspecialchars($e['email']) ?></td>
          <td>
            <details class="edit-disclosure">
              <summary class="btn">Configurar</summary>
              <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/employees/schedule') ?>" class="form-grid compact-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="employee_id" value="<?= (int) $e['id'] ?>">
                <?php $days = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo']; ?>
                <?php foreach ($days as $dayIndex => $dayLabel): ?>
                <label><?= $dayLabel ?> (ej: 08:00-12:00,13:00-17:00)
                  <input name="day_<?= $dayIndex ?>" value="<?= htmlspecialchars(implode(',', $employeeSchedule[$dayIndex] ?? [])) ?>" placeholder="Sin turno">
                </label>
                <?php endforeach; ?>
                <button class="btn btn-primary" type="submit">Guardar horario</button>
              </form>
            </details>
          </td>
          <td><span class="badge <?= ((int) ($e['is_active'] ?? 0) === 1) ? 'ok' : 'warn' ?>"><?= ((int) ($e['is_active'] ?? 0) === 1) ? 'Activo' : 'Inactivo' ?></span></td>
          <td>
            <details class="edit-disclosure">
              <summary class="btn">Editar</summary>
              <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/employees/update') ?>" class="form-grid compact-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
                <label>ID corto<input name="short_id" maxlength="32" value="<?= htmlspecialchars((string) ($e['short_id'] ?? '')) ?>"></label>
                <label>Nombre<input name="full_name" required value="<?= htmlspecialchars($e['full_name']) ?>"></label>
                <label>Email<input type="email" name="email" required value="<?= htmlspecialchars($e['email']) ?>"></label>
                <label>PIN (opcional)<input name="pin" maxlength="6" placeholder="Dejar vacío para no cambiar"></label>
                <label>Estatus
                  <select name="status">
                    <option <?= ((int) ($e['is_active'] ?? 0) === 1) ? 'selected' : '' ?>>Activo</option>
                    <option <?= ((int) ($e['is_active'] ?? 0) !== 1) ? 'selected' : '' ?>>Inactivo</option>
                  </select>
                </label>
                <button class="btn btn-primary" type="submit">Actualizar</button>
              </form>
            </details>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
