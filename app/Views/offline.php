<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7C3AED">
    <title>Sin conexión · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/jacob.css') ?>">
</head>
<body>
<main class="auth-page">
    <div class="auth-card text-center">
        <i class="bi bi-wifi-off error-icon"></i>
        <h1 class="h4 fw-bold mt-2">Sin conexión</h1>
        <p class="text-body-secondary">No hay señal en este momento. Lo que ya guardaste está a salvo en el servidor.</p>
        <p class="small text-body-secondary mb-4">Probá acercarte a la entrada del local o activá los datos móviles.</p>
        <button class="btn btn-primary btn-xl w-100" type="button" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
        </button>
    </div>
</main>
</body>
</html>
