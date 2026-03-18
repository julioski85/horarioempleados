<div class="auth-shell">
  <section class="auth-visual-panel">
    <div class="auth-visual-content">
      <p class="auth-kicker">HorarioPro</p>
      <h1 class="auth-title">Una sola puerta de acceso para todo tu equipo.</h1>
      <p class="auth-subtitle">El sistema detecta automáticamente tu rol para llevarte al panel correcto con una experiencia consistente en light y dark mode.</p>
      <img class="auth-illustration" src="<?= htmlspecialchars(($base_path ?? '') . '/assets/uploads/base/illustration-dashboard.webp') ?>" alt="Ilustración del panel de HorarioPro">
    </div>
  </section>

  <section class="auth-form-panel">
    <div class="auth-card">
      <h2>Inicia sesión</h2>
      <p>Usa tus credenciales y te redirigimos según tu rol.</p>
      <?php if (!empty($error)): ?><div class="alert" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form method="post" action="<?= htmlspecialchars(($base_path ?? '') . '/login') ?>" class="auth-form-grid single-login-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <label for="login-email">Correo electrónico</label>
        <input id="login-email" class="auth-input" name="email" type="email" placeholder="tu-correo@empresa.com" required>
        <label for="login-password">Contraseña</label>
        <input id="login-password" class="auth-input" name="password" type="password" placeholder="••••••••" required>
        <button class="auth-button" type="submit">Continuar</button>
      </form>
    </div>
  </section>
</div>
