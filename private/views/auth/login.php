<div class="login-wrap">
  <div class="login-card">
    <h2>Bienvenido a HorarioPro</h2>
    <p>Control de asistencia premium</p>
    <?php if (!empty($error)): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="login-grid">
      <form method="post" action="/login/admin" class="panel">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <h3>Acceso administrador</h3>
        <input name="email" type="email" placeholder="admin@gym.local" required>
        <input name="password" type="password" placeholder="••••••••" required>
        <button class="btn btn-primary" type="submit">Entrar al panel</button>
      </form>
      <form method="post" action="/login/employee" class="panel">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <h3>Acceso empleado</h3>
        <input name="email" type="email" placeholder="ana@gym.local" required>
        <input name="password" type="password" placeholder="••••••••" required>
        <button class="btn" type="submit">Entrar como empleado</button>
      </form>
    </div>
  </div>
</div>
