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
</head>
<body>
<?= $content ?>
<script>window.APP_BASE_PATH = <?= json_encode($base_path ?? '') ?>;</script>
<script src="<?= htmlspecialchars(($base_path ?? '') . '/assets/js/app.js') ?>"></script>
</body>
</html>
