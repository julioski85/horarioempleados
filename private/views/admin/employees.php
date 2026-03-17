<div class="split-grid">
  <section class="card form-card">
    <h3>Nuevo empleado</h3>
    <form method="post" action="/admin/employees/save" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
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
      <thead><tr><th>Empleado</th><th>Email</th><th>PIN</th><th>Estatus</th></tr></thead>
      <tbody>
      <?php foreach ($employees as $e): ?>
      <tr>
        <td><?= htmlspecialchars($e['full_name']) ?></td>
        <td><?= htmlspecialchars($e['email']) ?></td>
        <td><?= htmlspecialchars($e['pin']) ?></td>
        <td><span class="badge <?= $e['status'] === 'Activo' ? 'ok' : 'warn' ?>"><?= htmlspecialchars($e['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
