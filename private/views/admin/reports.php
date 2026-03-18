<section class="card table-card">
  <div class="card-head">
    <div>
      <h3>Reporte de asistencia</h3>
      <p class="card-subtitle">Exporta y revisa el histórico más reciente del equipo.</p>
    </div>
    <a href="<?= htmlspecialchars(($base_path ?? '') . '/admin/reports/export-csv') ?>" class="btn btn-primary">Exportar CSV</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Empleado</th><th>Evento</th><th>Fecha</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr><td><?= htmlspecialchars($row['full_name']) ?></td><td><?= htmlspecialchars($row['event_type']) ?></td><td><?= htmlspecialchars($row['created_at']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
