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
<?php if (($show_floating_theme ?? true) === true): ?>
<button type="button" class="theme-toggle theme-toggle-floating" data-theme-toggle aria-label="Cambiar tema">🌙 Tema</button>
<?php endif; ?>
<?= $content ?>
<script>window.APP_BASE_PATH = <?= json_encode($base_path ?? '') ?>;</script>
<script src="<?= htmlspecialchars(($base_path ?? '') . '/assets/js/app.js') ?>"></script>
</body>
</html>
