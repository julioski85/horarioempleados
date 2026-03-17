<?php $title = 'Panel empleado'; require __DIR__ . '/../layouts/header.php'; ?>
<section class="grid two">
<article class="card">
<h2>Solicitar vacaciones/permisos</h2>
<form method="post" action="/employee/request">
<input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
<label>Tipo <select name="request_type"><option value="vacation">Vacaciones</option><option value="permission">Permiso</option></select></label>
<label>Desde <input type="date" name="start_date" required></label>
<label>Hasta <input type="date" name="end_date" required></label>
<label>Motivo <textarea name="reason" required></textarea></label>
<button>Enviar solicitud</button>
</form>
</article>
<article class="card"><h2>Mis solicitudes</h2><ul><?php foreach($requests as $r): ?><li><?= e($r['request_type'].' '.$r['start_date'].' '.$r['status']) ?></li><?php endforeach; ?></ul></article>
</section>
<section class="card"><h2>Historial reciente</h2><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Estatus</th></tr></thead><tbody><?php foreach($history as $h): ?><tr><td><?= e($h['recorded_at']) ?></td><td><?= e($h['record_type']) ?></td><td><?= e($h['status']) ?></td></tr><?php endforeach; ?></tbody></table></section>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
