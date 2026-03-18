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
  <div class="card-head">
    <div>
      <h3>Actividad reciente</h3>
      <p class="card-subtitle">Últimos registros de entrada/salida confirmados.</p>
    </div>
    <button class="btn btn-primary" type="button">Filtrar</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Empleado</th><th>Evento</th><th>Fecha</th><th>Estado</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['full_name']) ?></td>
          <td><?= htmlspecialchars(ucfirst($row['event_type'])) ?></td>
          <td><?= htmlspecialchars($row['created_at']) ?></td>
          <td><span class="badge ok"><?= htmlspecialchars($row['status'] ?? 'Confirmado') ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="show-grid">
  <article class="card panel-stack">
    <h3>Flujo semanal</h3>
    <p class="card-subtitle">Visual premium para incidencias y tendencias de la semana.</p>
    <span class="badge ok">Sin bloqueos críticos</span>
  </article>
  <article class="card panel-stack">
    <h3>Rendimiento de áreas</h3>
    <p class="card-subtitle">Resumen ejecutivo consolidado para jefaturas y RRHH.</p>
    <span class="badge">Datos sincronizados</span>
  </article>
</section>
