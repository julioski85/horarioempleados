<?php $title = 'Reportes'; require __DIR__ . '/../layouts/header.php'; ?>
<section class="card"><h2>Reporte de asistencias</h2><p><a class="button" href="/admin/reports/export-csv">Exportar CSV</a></p>
<table><thead><tr><th>Fecha</th><th>Empleado</th><th>Tipo</th><th>Estatus</th><th>Origen</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['recorded_at']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['record_type']) ?></td><td><?= e($r['status']) ?></td><td><?= e($r['origin']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></section>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
