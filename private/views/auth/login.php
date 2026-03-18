<div class="auth-shell">
  <section class="auth-visual-panel">
    <div class="auth-visual-content">
      <p class="auth-kicker">HorarioPro</p>
      <h1 class="auth-title">Gestiona asistencia con claridad y precisión.</h1>
      <p class="auth-subtitle">Una experiencia moderna para controlar turnos, equipo y operación diaria desde un solo lugar.</p>
      <img class="auth-illustration" src="<?= htmlspecialchars(($base_path ?? '') . '/assets/uploads/base/illustration-dashboard.webp') ?>" alt="Ilustración del panel de HorarioPro">
    </div>
  </section>

  <section class="auth-form-panel">
    <div class="auth-card">
      <h2>Inicia sesión en tu cuenta</h2>
      <p>Selecciona el tipo de acceso e ingresa tus credenciales.</p>
      <?php if (!empty($error)): ?><div class="alert" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div class="auth-form-grid">
        <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/login/admin') ?>" class="panel">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <h3>Acceso administrador</h3>
          <label for="admin-email">Correo electrónico</label>
          <input id="admin-email" class="auth-input" name="email" type="email" placeholder="admin@gym.local" required>
          <label for="admin-password">Contraseña</label>
          <input id="admin-password" class="auth-input" name="password" type="password" placeholder="••••••••" required>
          <button class="auth-button" type="submit">Entrar al panel</button>
        </form>

        <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/login/employee') ?>" class="panel panel-secondary">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <h3>Acceso empleado</h3>
          <label for="employee-email">Correo electrónico</label>
          <input id="employee-email" class="auth-input" name="email" type="email" placeholder="ana@gym.local" required>
          <label for="employee-password">Contraseña</label>
          <input id="employee-password" class="auth-input" name="password" type="password" placeholder="••••••••" required>
          <button class="auth-button auth-button-secondary" type="submit">Entrar como empleado</button>
        </form>
      </div>
    </div>
  </section>
</div>
