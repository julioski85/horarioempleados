<section class="kpi-row">
  <article class="kpi"><span>Mi estado</span><strong>Activo</strong></article>
  <article class="kpi"><span>Solicitudes</span><strong><?= count($requests) ?></strong></article>
  <article class="kpi"><span>Marcajes recientes</span><strong><?= count($rows) ?></strong></article>
  <article class="kpi"><span>Modo</span><strong>Autogestión</strong></article>
</section>

<div class="split-grid">
  <section class="card table-card">
    <div class="card-head">
      <div>
        <h3>Mis marcajes</h3>
        <p class="card-subtitle">Entradas y salidas registradas recientemente.</p>
      </div>
      <span class="badge">Histórico</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Evento</th><th>Fecha</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr><td><?= htmlspecialchars($r['event_type']) ?></td><td><?= htmlspecialchars($r['created_at']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="card form-card">
    <div class="card-head">
      <div>
        <h3>Nueva solicitud</h3>
        <p class="card-subtitle">Solicita permisos o vacaciones en un clic.</p>
      </div>
      <span class="badge">Self-service</span>
    </div>
    <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/employee/request') ?>" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label>Tipo<select name="type"><option>Permiso</option><option>Vacaciones</option></select></label>
      <button class="btn btn-primary" type="submit">Enviar</button>
    </form>
  </section>
</div>
