<?php $title = 'Dashboard admin'; require __DIR__ . '/../layouts/header.php'; ?>
<section class="grid five">
    <?php foreach ($kpis as $k => $v): ?>
        <article class="card"><h3><?= e($k) ?></h3><p class="kpi"><?= e((string)$v) ?></p></article>
    <?php endforeach; ?>
</section>
<section class="card">
    <h2>Actividad reciente kiosco</h2>
    <table><thead><tr><th>Fecha</th><th>Empleado</th><th>Tipo</th><th>Estatus</th></tr></thead><tbody>
    <?php foreach ($recent as $r): ?>
        <tr><td><?= e($r['recorded_at']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['record_type']) ?></td><td><?= e($r['status']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <p><a href="/admin/employees">Gestionar empleados</a> · <a href="/admin/requests">Solicitudes</a> · <a href="/admin/reports">Reportes</a></p>
</section>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
