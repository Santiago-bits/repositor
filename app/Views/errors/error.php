<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7C3AED">
    <title><?= (int) $code ?> · <?= e(APP_NAME) ?></title>
    <script>
        (function () {
            var t = 'auto';
            try { t = localStorage.getItem('jacob-theme') || 'auto'; } catch (e) {}
            var dark = t === 'dark' || (t === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        })();
    </script>
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/jacob.css') ?>">
</head>
<body>
<main class="auth-page">
    <div class="auth-card text-center">
        <i class="bi <?= $code >= 500 ? 'bi-exclamation-triangle' : 'bi-signpost-split' ?> error-icon"></i>
        <p class="error-code"><?= (int) $code ?></p>
        <p class="mb-4"><?= e($message) ?></p>
        <a class="btn btn-primary btn-xl w-100" href="<?= url('/') ?>"><i class="bi bi-house-door me-1"></i> Volver al inicio</a>
    </div>
</main>
</body>
</html>
