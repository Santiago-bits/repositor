<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#7C3AED">
<meta name="csrf-token" content="<?= e(App\Core\Csrf::token()) ?>">
<title><?= e(!empty($title) ? $title . ' · ' . APP_NAME : APP_NAME) ?></title>
<meta name="base-url" content="<?= e(url('/')) ?>">
<meta name="description" content="<?= e(APP_TAGLINE) ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_NAME) ?>">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="manifest" href="<?= url('/manifest.webmanifest') ?>">
<link rel="icon" href="<?= asset('assets/img/logo.svg') ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= asset('assets/icons/apple-touch-icon.png') ?>">
<script>
    // Aplica el tema guardado antes de pintar, para que no parpadee.
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
