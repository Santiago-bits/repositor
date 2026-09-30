<?php
$v = fn (string $campo) => old($campo, $usuario[$campo] ?? '');
$activo = has_old() ? (bool) old('activo', false) : (bool) ($usuario['activo'] ?? true);
$rolActual = (int) $v('role_id') ?: (int) (array_column($roles, 'id', 'slug')['repositor'] ?? 0);
$asignados = array_map('intval', (array) old('locales', $asignados));
$esYo = $usuario && (int) $usuario['id'] === App\Core\Auth::id();
?>

<div class="page-head">
    <h1><?= e($title) ?></h1>
    <a class="btn btn-link" href="<?= url('/admin/usuarios') ?>">Cancelar</a>
</div>

<form method="post" action="<?= url($usuario ? "/admin/usuarios/{$usuario['id']}" : '/admin/usuarios') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label" for="nombre">Nombre</label>
            <input class="form-control<?= invalid('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" maxlength="80" autocomplete="off" required autofocus>
            <?= field_error('nombre') ?>
        </div>
        <div class="col-6">
            <label class="form-label" for="apellido">Apellido</label>
            <input class="form-control<?= invalid('apellido') ?>" id="apellido" name="apellido" value="<?= e($v('apellido')) ?>" maxlength="80" autocomplete="off" required>
            <?= field_error('apellido') ?>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control<?= invalid('email') ?>" type="email" id="email" name="email" value="<?= e($v('email')) ?>" maxlength="150" inputmode="email" autocapitalize="off" autocomplete="off" required>
        <?= field_error('email') ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">Contraseña</label>
        <div class="input-group">
            <input class="form-control<?= invalid('password') ?>" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" <?= $usuario ? '' : 'required' ?>>
            <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
        </div>
        <?= field_error('password') ?>
        <div class="form-text"><?= $usuario ? 'Dejala vacía para mantener la actual.' : 'Mínimo 8 caracteres.' ?></div>
    </div>

    <div class="mb-3">
        <span class="form-label d-block">Rol</span>
        <div class="option-grid">
            <?php foreach ($roles as $rol): ?>
                <input type="radio" class="btn-check" name="role_id" id="rol-<?= $rol['id'] ?>" value="<?= $rol['id'] ?>" <?= $rolActual === (int) $rol['id'] ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="rol-<?= $rol['id'] ?>">
                    <i class="bi <?= $rol['slug'] === 'admin' ? 'bi-shield-lock' : 'bi-person-badge' ?>"></i> <?= e($rol['nombre']) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?= field_error('role_id') ?>
    </div>

    <div class="mb-3">
        <span class="form-label d-block">Locales asignados</span>
        <?php if ($locales === []): ?>
            <p class="text-body-secondary small">No hay locales cargados todavía.</p>
        <?php else: ?>
            <div class="card-soft check-list">
                <?php foreach ($locales as $l): ?>
                    <label class="form-check check-row">
                        <input class="form-check-input" type="checkbox" name="locales[]" value="<?= $l['id'] ?>" <?= in_array((int) $l['id'], $asignados, true) ? 'checked' : '' ?>>
                        <span class="form-check-label"><?= e($l['nombre']) ?><?php if (!$l['activo']): ?> <span class="badge text-bg-secondary">Inactivo</span><?php endif; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="form-text">Los administradores ven todos los locales, tengan asignación o no.</div>
    </div>

    <div class="card-soft px-3 py-2 mb-3">
        <div class="form-check form-switch py-2">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $activo ? 'checked' : '' ?> <?= $esYo ? 'disabled' : '' ?>>
            <label class="form-check-label" for="activo">Usuario activo</label>
            <?php if ($esYo): ?><input type="hidden" name="activo" value="1"><?php endif; ?>
        </div>
        <?= field_error('activo') ?>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar</button>
    </div>
</form>

<?php if ($usuario && !$esYo): ?>
    <form method="post" action="<?= url("/admin/usuarios/{$usuario['id']}/estado") ?>" class="mt-3"
          data-confirm="<?= $usuario['activo'] ? '¿Desactivar este usuario? No va a poder ingresar, pero se conserva su historial.' : '¿Volver a activar este usuario?' ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-<?= $usuario['activo'] ? 'danger' : 'success' ?> w-100" type="submit">
            <i class="bi bi-<?= $usuario['activo'] ? 'person-slash' : 'person-check' ?> me-1"></i>
            <?= $usuario['activo'] ? 'Desactivar usuario' : 'Activar usuario' ?>
        </button>
    </form>
<?php endif; ?>
