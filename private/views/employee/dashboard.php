<section class="kpi-row">
  <article class="kpi"><span>Mi estado</span><strong>Activo</strong></article>
  <article class="kpi"><span>Solicitudes</span><strong><?= count($requests) ?></strong></article>
</section>
<div class="split-grid">
  <section class="card table-card">
    <h3>Mis marcajes</h3>
    <table><thead><tr><th>Evento</th><th>Fecha</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['event_type']) ?></td><td><?= htmlspecialchars($r['created_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </section>
  <section class="card form-card">
    <h3>Nueva solicitud</h3>
    <form method="post" action="/employee/request" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label>Tipo<select name="type"><option>Permiso</option><option>Vacaciones</option></select></label>
      <button class="btn btn-primary" type="submit">Enviar</button>
    </form>
  </section>
</div>
