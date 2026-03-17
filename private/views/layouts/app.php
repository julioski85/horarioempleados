<?php use App\Core\Auth; $user = Auth::user(); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Asistencia') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="logo">Horario<span>Pro</span></div>
    <nav>
      <a href="/admin/dashboard">Dashboard</a>
      <a href="/admin/employees">Empleados</a>
      <a href="/admin/requests">Solicitudes</a>
      <a href="/admin/reports">Reportes</a>
      <a href="/kiosk">Kiosco</a>
    </nav>
    <a class="logout" href="/logout">Cerrar sesión</a>
  </aside>
  <main class="main-area">
    <header class="topbar">
      <div>
        <h1><?= htmlspecialchars($title ?? '') ?></h1>
        <p>Panel de control de asistencia</p>
      </div>
      <div class="user-pill"><?= htmlspecialchars($user['name'] ?? 'Usuario') ?></div>
    </header>
    <?= $content ?>
  </main>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
