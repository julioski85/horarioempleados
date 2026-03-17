<?php $title = 'Empleados'; require __DIR__ . '/../layouts/header.php'; ?>
<section class="card">
    <h2>Nuevo empleado</h2>
    <form method="post" action="/admin/employees/save" class="grid three">
        <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
        <label>ID corto <input name="short_id" required></label>
        <label>Nombre completo <input name="full_name" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>PIN 4 dígitos <input name="pin" pattern="\\d{4}" required></label>
        <label>Password inicial <input name="password" required></label>
        <label>Área ID <input name="area_id" type="number" value="1"></label>
        <label><input type="checkbox" name="is_active" checked> Activo</label>
        <button>Guardar</button>
    </form>
</section>
<section class="card">
    <h2>Listado</h2>
    <table><thead><tr><th>ID</th><th>ID corto</th><th>Nombre</th><th>Email</th><th>Activo</th></tr></thead><tbody>
        <?php foreach ($employees as $e): ?>
            <tr><td><?= e((string)$e['id']) ?></td><td><?= e($e['short_id']) ?></td><td><?= e($e['full_name']) ?></td><td><?= e($e['email']) ?></td><td><?= (int)$e['is_active'] ? 'Sí':'No' ?></td></tr>
        <?php endforeach; ?>
    </tbody></table>
</section>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
