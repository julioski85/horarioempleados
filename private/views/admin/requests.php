<section class="card table-card">
  <h3>Solicitudes del equipo</h3>
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
          <form method="post" action="/admin/requests/status" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <select name="status"><option>Pendiente</option><option>Aprobada</option><option>Rechazada</option></select>
            <button class="btn" type="submit">Actualizar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
