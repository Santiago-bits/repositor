<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <?= partial('head', ['title' => $title ?? null]) ?>
</head>
<body>
<main class="auth-page">
    <?= $content ?>
</main>
<script src="<?= asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>" defer></script>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
