<?php $user = auth(); ?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <?= partial('head', ['title' => $title ?? null]) ?>
</head>
<body>
<header class="app-header">
    <a class="brand" href="<?= url('/') ?>">
        <img src="<?= asset('assets/img/logo.svg') ?>" alt="" width="34" height="34">
        <span>
            <strong><?= e(APP_NAME) ?></strong>
            <small class="d-none d-sm-block"><?= e(APP_TAGLINE) ?></small>
        </span>
    </a>
    <?php if ($user): ?>
        <a class="avatar" href="<?= url('/perfil') ?>" title="Perfil"><?= e(initials($user)) ?></a>
    <?php endif; ?>
</header>

<div class="app-shell">
    <aside class="app-sidebar d-none d-lg-block">
        <nav class="side-nav">
            <?php foreach (nav_principal() as [$path, $icon, $label]): ?>
                <a class="nav-link <?= active($path) ?>" href="<?= url($path) ?>"><i class="bi <?= $icon ?>"></i><span><?= $label ?></span></a>
            <?php endforeach; ?>
            <?php if (is_admin()): ?>
                <div class="side-title">Gestión</div>
                <?php foreach (nav_admin() as [$path, $icon, $label]): ?>
                    <a class="nav-link <?= active($path, $path === '/admin') ?>" href="<?= url($path) ?>"><i class="bi <?= $icon ?>"></i><span><?= $label ?></span></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="app-main">
        <?= partial('flash') ?>
        <?= $content ?>
    </main>
</div>

<nav class="bottom-nav d-lg-none" aria-label="Navegación principal">
    <?php foreach (nav_principal() as [$path, $icon, $label]): ?>
        <a class="<?= active($path) ?>" href="<?= url($path) ?>"><i class="bi <?= $icon ?>"></i><span><?= $label ?></span></a>
    <?php endforeach; ?>
</nav>

<script src="<?= asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>" defer></script>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
<?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= asset($script) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
