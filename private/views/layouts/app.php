<?php use App\Core\Auth; $user = Auth::user(); $role = $user['role'] ?? ''; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Asistencia') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars(($base_path ?? '') . '/assets/css/app.css') ?>">
  <script>
    (function () {
      var savedTheme = localStorage.getItem('app-theme');
      var theme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-theme', theme);
    })();
  </script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="logo">Horario<span>Pro</span></div>
    <nav>
      <?php if ($role === 'admin'): ?>
      <a href="<?= htmlspecialchars(($base_path ?? '') . '/admin/dashboard') ?>">Dashboard</a>
      <a href="<?= htmlspecialchars(($base_path ?? '') . '/admin/employees') ?>">Empleados</a>
      <a href="<?= htmlspecialchars(($base_path ?? '') . '/admin/requests') ?>">Solicitudes</a>
      <a href="<?= htmlspecialchars(($base_path ?? '') . '/admin/reports') ?>">Reportes</a>
      <?php endif; ?>
      <?php if ($role === 'employee'): ?>
      <a href="<?= htmlspecialchars(($base_path ?? '') . '/employee/dashboard') ?>">Mi panel</a>
      <?php endif; ?>
      <a href="<?= htmlspecialchars(($base_path ?? '') . '/kiosk') ?>">Kiosco</a>
    </nav>
    <a class="logout" href="<?= htmlspecialchars(($base_path ?? '') . '/logout') ?>">Cerrar sesión</a>
  </aside>
  <main class="main-area">
    <header class="topbar">
      <div>
        <h1><?= htmlspecialchars($title ?? '') ?></h1>
        <p><?= $role === 'admin' ? 'Panel de control de asistencia' : 'Tu espacio de gestión de asistencia' ?></p>
      </div>
      <div class="topbar-actions">
        <button type="button" class="theme-toggle" data-theme-toggle aria-label="Cambiar tema">🌙 Tema</button>
        <div class="user-pill"><?= htmlspecialchars($user['name'] ?? 'Usuario') ?></div>
      </div>
    </header>
    <?= $content ?>
  </main>
</div>
<script>window.APP_BASE_PATH = <?= json_encode($base_path ?? '') ?>;</script>
<script src="<?= htmlspecialchars(($base_path ?? '') . '/assets/js/app.js') ?>"></script>
</body>
</html>
