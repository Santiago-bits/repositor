<?php
$v = fn (string $campo) => old($campo, $user[$campo] ?? '');
?>
<div class="page-head">
    <h1>Mi cuenta</h1>
</div>

<div class="card-soft p-4 text-center mb-2">
    <form method="post" action="<?= url('/perfil/foto') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label class="cuenta-foto mx-auto mb-3" title="Cambiar foto">
            <?= avatar($user, 'avatar-lg') ?>
            <span class="cuenta-foto-cam"><i class="bi bi-camera-fill"></i></span>
            <input class="visually-hidden" type="file" name="foto" accept="image/*" data-autosubmit aria-label="Cambiar foto de perfil">
        </label>
    </form>
    <?= field_error('foto') ?>
    <h2 class="h4 fw-bold mb-1"><?= e($user['nombre'] . ' ' . $user['apellido']) ?></h2>
    <p class="text-body-secondary mb-2"><?= e($user['email']) ?></p>
    <span class="badge badge-soft"><?= e($user['role_nombre']) ?></span>
    <?php if (!empty($user['foto_path'])): ?>
        <form method="post" action="<?= url('/perfil/foto/quitar') ?>" class="mt-2" data-confirm="¿Quitar tu foto?">
            <?= csrf_field() ?>
            <button class="btn btn-link btn-sm text-danger" type="submit">Quitar foto</button>
        </form>
    <?php else: ?>
        <p class="small text-body-secondary mt-2 mb-0">Tocá el círculo para poner tu foto.</p>
    <?php endif; ?>
</div>

<h2 class="section-title">Tus datos</h2>
<form class="card-soft p-3" method="post" action="<?= url('/perfil/datos') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="row g-2 mb-2">
        <div class="col-6">
            <label class="form-label small" for="nombre">Nombre</label>
            <input class="form-control<?= invalid('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" maxlength="80" autocomplete="given-name" required>
            <?= field_error('nombre') ?>
        </div>
        <div class="col-6">
            <label class="form-label small" for="apellido">Apellido</label>
            <input class="form-control<?= invalid('apellido') ?>" id="apellido" name="apellido" value="<?= e($v('apellido')) ?>" maxlength="80" autocomplete="family-name" required>
            <?= field_error('apellido') ?>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small" for="telefono">Teléfono / WhatsApp <span class="text-body-secondary">(opcional)</span></label>
        <input class="form-control<?= invalid('telefono') ?>" type="tel" id="telefono" name="telefono" value="<?= e($v('telefono')) ?>" maxlength="40" inputmode="tel" autocomplete="tel" placeholder="Ej: 345 412-3456">
        <?= field_error('telefono') ?>
    </div>
    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar datos</button>
</form>

<h2 class="section-title" id="password">Contraseña</h2>
<details class="card-soft cuenta-plegable"<?= error_for('actual') || error_for('nueva') || error_for('repetir') ? ' open' : '' ?>>
    <summary><i class="bi bi-key"></i> Cambiar contraseña</summary>
    <form method="post" action="<?= url('/perfil/password') ?>" class="pt-2" novalidate>
        <?= csrf_field() ?>
        <div class="mb-2">
            <label class="form-label small" for="actual">Contraseña actual</label>
            <input class="form-control<?= invalid('actual') ?>" type="password" id="actual" name="actual" autocomplete="current-password" required>
            <?= field_error('actual') ?>
        </div>
        <div class="mb-2">
            <label class="form-label small" for="nueva">Contraseña nueva <span class="text-body-secondary">(mínimo 8)</span></label>
            <div class="input-group">
                <input class="form-control<?= invalid('nueva') ?>" type="password" id="nueva" name="nueva" minlength="8" maxlength="72" autocomplete="new-password" required>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="nueva" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
            </div>
            <?= field_error('nueva') ?>
        </div>
        <div class="mb-3">
            <label class="form-label small" for="repetir">Repetí la contraseña nueva</label>
            <input class="form-control<?= invalid('repetir') ?>" type="password" id="repetir" name="repetir" maxlength="72" autocomplete="new-password" required>
            <?= field_error('repetir') ?>
        </div>
        <button class="btn btn-primary w-100" type="submit">Cambiar contraseña</button>
        <p class="small text-body-secondary text-center mt-2 mb-0">Se cierra tu sesión en los demás celulares.</p>
    </form>
</details>

<h2 class="section-title">Apariencia</h2>
<div class="btn-group w-100 theme-switch" role="group" aria-label="Tema">
    <button type="button" class="btn btn-outline-primary" data-theme-set="light"><i class="bi bi-sun"></i> Claro</button>
    <button type="button" class="btn btn-outline-primary" data-theme-set="dark"><i class="bi bi-moon-stars"></i> Oscuro</button>
    <button type="button" class="btn btn-outline-primary" data-theme-set="auto"><i class="bi bi-circle-half"></i> Auto</button>
</div>

<h2 class="section-title">App</h2>
<button class="btn btn-primary btn-xl w-100" type="button" data-instalar hidden>
    <i class="bi bi-phone me-1"></i> Instalar la app en el celular
</button>
<div class="notice" data-ios-instalar hidden>
    <i class="bi bi-box-arrow-up"></i>
    <div>En iPhone: tocá <strong>Compartir</strong> <i class="bi bi-box-arrow-up"></i> y después <strong>Agregar a inicio</strong>.</div>
</div>
<p class="small text-body-secondary text-center mt-2 mb-0">Instalada, se abre como una app, a pantalla completa y más rápido.</p>

<h2 class="section-title">Seguridad</h2>
<form method="post" action="<?= url('/perfil/sesiones') ?>"
      data-confirm="¿Cerrar tu sesión en todos los otros celulares y computadoras? En este seguís adentro.">
    <?= csrf_field() ?>
    <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-phone-flip me-1"></i> Cerrar sesión en los otros dispositivos</button>
</form>
<p class="small text-body-secondary text-center mt-2 mb-0">Por si entraste desde otro celular o perdiste el tuyo.</p>

<form method="post" action="<?= url('/logout') ?>" class="mt-4">
    <?= csrf_field() ?>
    <button class="btn btn-outline-danger btn-xl w-100" type="submit">
        <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
    </button>
</form>
