<section class="card table-card">
  <div class="card-head"><h3>Solicitudes del equipo</h3><span class="badge">Workflow</span></div>
  <table>
    <thead><tr><th>Empleado</th><th>Tipo</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead>
    <tbody>
      <?php foreach ($requests as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['full_name']) ?></td>
        <td><?= htmlspecialchars($r['type']) ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td><span class="badge"><?= htmlspecialchars($r['status']) ?></span></td>
        <td>
          <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/admin/requests/status') ?>" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <select name="status">
              <option <?= $r['status'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
              <option <?= $r['status'] === 'Aprobada' ? 'selected' : '' ?>>Aprobada</option>
              <option <?= $r['status'] === 'Rechazada' ? 'selected' : '' ?>>Rechazada</option>
            </select>
            <button class="btn" type="submit">Actualizar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
