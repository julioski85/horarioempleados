<div class="split-grid">
  <section class="card form-card">
    <h3>Nuevo empleado</h3>
    <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/employees/save') ?>" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label>ID corto<input name="short_id" maxlength="32" placeholder="EMP-0001"></label>
      <label>Nombre completo<input name="full_name" required></label>
      <label>Email<input type="email" name="email" required></label>
      <label>PIN kiosco<input name="pin" maxlength="6" required></label>
      <label>Estatus<select name="status"><option>Activo</option><option>Inactivo</option></select></label>
      <button class="btn btn-primary" type="submit">Guardar empleado</button>
    </form>
  </section>
  <section class="card table-card">
    <h3>Gestión de empleados</h3>
    <table>
      <thead><tr><th>ID corto</th><th>Empleado</th><th>Email</th><th>Estatus</th></tr></thead>
      <tbody>
      <?php foreach ($employees as $e): ?>
      <tr>
        <td><?= htmlspecialchars((string) ($e['short_id'] ?? '')) ?></td>
        <td><?= htmlspecialchars($e['full_name']) ?></td>
        <td><?= htmlspecialchars($e['email']) ?></td>
        <td><span class="badge <?= ((int) ($e['is_active'] ?? 0) === 1) ? 'ok' : 'warn' ?>"><?= ((int) ($e['is_active'] ?? 0) === 1) ? 'Activo' : 'Inactivo' ?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
