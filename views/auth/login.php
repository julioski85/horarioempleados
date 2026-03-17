<?php $title = 'Ingreso seguro'; require __DIR__ . '/../layouts/header.php'; ?>
<div class="grid two">
    <section class="card">
        <h2>Administrador</h2>
        <?php if ($msg = flash('error')): ?><p class="alert error"><?= e($msg) ?></p><?php endif; ?>
        <form method="post" action="/login/admin">
            <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
            <label>Email <input type="email" name="email" required></label>
            <label>Contraseña <input type="password" name="password" required></label>
            <button>Entrar como admin</button>
        </form>
    </section>
    <section class="card">
        <h2>Empleado</h2>
        <form method="post" action="/login/employee">
            <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
            <label>Email <input type="email" name="email" required></label>
            <label>Contraseña <input type="password" name="password" required></label>
            <button>Entrar como empleado</button>
        </form>
        <p><a href="/kiosk">Ir a modo kiosco</a></p>
    </section>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
