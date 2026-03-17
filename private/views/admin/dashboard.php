<?php if (!empty($dashboard_error)): ?>
<section class="card"><p class="alert"><?= htmlspecialchars($dashboard_error) ?></p></section>
<?php endif; ?>
<section class="kpi-row">
  <article class="kpi"><span>Total empleados</span><strong><?= (int) $kpis['employees'] ?></strong></article>
  <article class="kpi"><span>Registros hoy</span><strong><?= (int) $kpis['attendance_today'] ?></strong></article>
  <article class="kpi"><span>Solicitudes pendientes</span><strong><?= (int) $kpis['pending_requests'] ?></strong></article>
  <article class="kpi"><span>Tasa activa</span><strong><?= (int) $kpis['active_rate'] ?>%</strong></article>
</section>
<section class="card table-card">
  <div class="card-head"><h3>Actividad reciente</h3><button class="btn btn-primary">Filtrar</button></div>
  <table>
    <thead><tr><th>Empleado</th><th>Evento</th><th>Fecha</th><th>Estado</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
        <td><?= htmlspecialchars(ucfirst($row['event_type'])) ?></td>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
        <td><span class="badge ok">Confirmado</span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<section class="show-grid">
  <article class="card"><h3>Flujo semanal</h3><p>Visual premium para incidencias y tendencias.</p></article>
  <article class="card"><h3>Rendimiento de áreas</h3><p>Resumen ejecutivo tipo showcase como referencia.</p></article>
</section>
