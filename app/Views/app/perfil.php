<div class="card-soft p-4 text-center mb-4">
    <div class="avatar-lg mx-auto mb-3"><?= e(initials($user)) ?></div>
    <h1 class="h4 fw-bold mb-1"><?= e($user['nombre'] . ' ' . $user['apellido']) ?></h1>
    <p class="text-body-secondary mb-2"><?= e($user['email']) ?></p>
    <span class="badge badge-soft"><?= e($user['role_nombre']) ?></span>
</div>

<h2 class="section-title">Apariencia</h2>
<div class="btn-group w-100 theme-switch" role="group" aria-label="Tema">
    <button type="button" class="btn btn-outline-primary" data-theme-set="light"><i class="bi bi-sun"></i> Claro</button>
    <button type="button" class="btn btn-outline-primary" data-theme-set="dark"><i class="bi bi-moon-stars"></i> Oscuro</button>
    <button type="button" class="btn btn-outline-primary" data-theme-set="auto"><i class="bi bi-circle-half"></i> Auto</button>
</div>

<?php if ($googleClientId !== ''): ?>
    <h2 class="section-title">Cuenta de Google</h2>
    <div class="card-soft p-3">
        <?php if (!empty($user['google_email'])): ?>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-google fs-4 text-primary"></i>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold">Vinculada</div>
                    <div class="small text-body-secondary text-truncate"><?= e($user['google_email']) ?></div>
                </div>
                <form method="post" action="<?= url('/perfil/google/quitar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit">Quitar</button>
                </form>
            </div>
        <?php else: ?>
            <p class="small text-body-secondary mb-2">Vinculá tu Gmail y la próxima vez entrás con un toque, sin contraseña.</p>
            <div class="google-btn" data-google data-texto="continue_with" data-client-id="<?= e($googleClientId) ?>" data-endpoint="<?= url('/perfil/google') ?>"></div>
            <p class="small text-danger mt-2 mb-0" data-google-estado role="status"></p>
            <script src="<?= asset('assets/js/google.js') ?>" defer></script>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h2 class="section-title">App</h2>
<button class="btn btn-primary btn-xl w-100" type="button" data-instalar hidden>
    <i class="bi bi-phone me-1"></i> Instalar la app en el celular
</button>
<div class="notice" data-ios-instalar hidden>
    <i class="bi bi-box-arrow-up"></i>
    <div>En iPhone: tocá <strong>Compartir</strong> <i class="bi bi-box-arrow-up"></i> y después <strong>Agregar a inicio</strong>.</div>
</div>
<p class="small text-body-secondary mt-2 mb-0">Instalada, se abre como una app, a pantalla completa y más rápido.</p>

<?php if (is_admin()): ?>
    <h2 class="section-title">Gestión</h2>
    <div class="item-list">
        <?php foreach (nav_admin() as [$path, $icon, $label]): ?>
            <a class="item-card" href="<?= url($path) ?>">
                <div class="item-icon"><i class="bi <?= $icon ?>"></i></div>
                <div class="item-body"><div class="item-title"><?= $label ?></div></div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/logout') ?>" class="mt-4">
    <?= csrf_field() ?>
    <button class="btn btn-outline-danger btn-xl w-100" type="submit">
        <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
    </button>
</form>
