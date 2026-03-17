<?php $title = 'Solicitudes'; require __DIR__ . '/../layouts/header.php'; ?>
<section class="card"><h2>Vacaciones y permisos</h2>
<table><thead><tr><th>Empleado</th><th>Tipo</th><th>Rango</th><th>Estatus</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= e($r['full_name']) ?></td><td><?= e($r['request_type']) ?></td><td><?= e($r['start_date'] . ' al ' . $r['end_date']) ?></td><td><?= e($r['status']) ?></td>
<td><form method="post" action="/admin/requests/status"><input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>"><input type="hidden" name="id" value="<?= e((string)$r['id']) ?>"><select name="status"><option>pending</option><option>approved</option><option>rejected</option></select><input name="admin_notes" placeholder="Nota"><button>Guardar</button></form></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</section>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
