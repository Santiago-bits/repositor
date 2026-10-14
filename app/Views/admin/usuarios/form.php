<?php
$v = fn (string $campo) => old($campo, $usuario[$campo] ?? '');
$activo = has_old() ? (bool) old('activo', false) : (bool) ($usuario['activo'] ?? true);
$rolActual = (int) $v('role_id') ?: (int) (array_column($roles, 'id', 'slug')['repositor'] ?? 0);
$esYo = $usuario && (int) $usuario['id'] === App\Core\Auth::id();
$rolInfo = [
    'admin'     => ['bi-shield-lock', 'Ve todo y maneja Gestión: locales, productos, promos y usuarios.'],
    'repositor' => ['bi-person-badge', 'Visita locales: vencimientos, faltantes, promos y fotos. Ve solo lo suyo.'],
    'vendedor'  => ['bi-briefcase', 'Ve los faltantes de los locales, las fechas cortas y los productos.'],
];
?>
<a class="back-link" href="<?= url('/admin/usuarios') ?>"><i class="bi bi-arrow-left"></i> Usuarios</a>

<div class="page-head">
    <h1><?= e($title) ?></h1>
    <?php if ($usuario): ?>
        <p class="page-sub"><?= e($usuario['email']) ?></p>
    <?php else: ?>
        <p class="page-sub">Con estos datos va a poder entrar a la app.</p>
    <?php endif; ?>
</div>

<form method="post" action="<?= url($usuario ? "/admin/usuarios/{$usuario['id']}" : '/admin/usuarios') ?>" novalidate>
    <?= csrf_field() ?>

    <h2 class="section-title mt-0">Datos</h2>
    <div class="card-soft p-3 mb-3">
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label small" for="nombre">Nombre</label>
                <input class="form-control<?= invalid('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" maxlength="80" autocomplete="off" required <?= $usuario ? '' : 'autofocus' ?>>
                <?= field_error('nombre') ?>
            </div>
            <div class="col-6">
                <label class="form-label small" for="apellido">Apellido</label>
                <input class="form-control<?= invalid('apellido') ?>" id="apellido" name="apellido" value="<?= e($v('apellido')) ?>" maxlength="80" autocomplete="off" required>
                <?= field_error('apellido') ?>
            </div>
        </div>
    </div>

    <h2 class="section-title">Acceso</h2>
    <div class="card-soft p-3 mb-3">
        <div class="mb-3">
            <label class="form-label small" for="email"><i class="bi bi-envelope"></i> Email</label>
            <input class="form-control<?= invalid('email') ?>" type="email" id="email" name="email" value="<?= e($v('email')) ?>" maxlength="150" inputmode="email" autocapitalize="off" autocomplete="off" placeholder="nombre@gmail.com" required>
            <?= field_error('email') ?>
        </div>

        <div class="mb-3">
            <label class="form-label small" for="password"><i class="bi bi-key"></i> Contraseña</label>
            <div class="input-group">
                <input class="form-control<?= invalid('password') ?>" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password"
                       placeholder="<?= $usuario ? 'Vacía = mantener la actual' : 'Mínimo 8 caracteres' ?>" <?= $usuario ? '' : 'required' ?>>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
            </div>
            <?= field_error('password') ?>
        </div>

        <details class="form-extra"<?= $v('google_email') !== '' || error_for('google_email') ? ' open' : '' ?>>
            <summary><i class="bi bi-google"></i> Entra con Google con otro Gmail</summary>
            <div class="pt-2">
                <input class="form-control<?= invalid('google_email') ?>" type="email" id="google_email" name="google_email" value="<?= e($v('google_email')) ?>" maxlength="190" inputmode="email" autocapitalize="off" autocomplete="off" placeholder="otro@gmail.com" aria-label="Gmail para entrar con Google">
                <?= field_error('google_email') ?>
                <div class="form-text">Solo si su Gmail es distinto del email de arriba. Si el de arriba ya es Gmail, no hace falta.</div>
            </div>
        </details>
    </div>

    <h2 class="section-title">Rol</h2>
    <div class="rol-opciones mb-3">
        <?php foreach ($roles as $rol): ?>
            <?php [$icono, $texto] = $rolInfo[$rol['slug']] ?? ['bi-person', '']; ?>
            <input type="radio" class="btn-check" name="role_id" id="rol-<?= $rol['id'] ?>" value="<?= $rol['id'] ?>" <?= $rolActual === (int) $rol['id'] ? 'checked' : '' ?>>
            <label class="rol-opcion" for="rol-<?= $rol['id'] ?>">
                <i class="bi <?= $icono ?>"></i>
                <span>
                    <strong><?= e($rol['nombre']) ?></strong>
                    <small><?= e($texto) ?></small>
                </span>
                <i class="bi bi-check-circle-fill rol-check"></i>
            </label>
        <?php endforeach; ?>
    </div>
    <?= field_error('role_id') ?>

    <div class="card-soft px-3 py-2 mb-3">
        <div class="form-check form-switch py-2 mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $activo ? 'checked' : '' ?> <?= $esYo ? 'disabled' : '' ?>>
            <label class="form-check-label" for="activo">Puede entrar a la app <span class="text-body-secondary small">(usuario activo)</span></label>
            <?php if ($esYo): ?><input type="hidden" name="activo" value="1"><?php endif; ?>
        </div>
        <?= field_error('activo') ?>
    </div>

    <div class="form-actions">
        <a class="btn btn-outline-secondary btn-xl" href="<?= url('/admin/usuarios') ?>">Cancelar</a>
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> <?= $usuario ? 'Guardar cambios' : 'Crear usuario' ?></button>
    </div>
</form>

<?php if ($usuario && !$esYo): ?>
    <h2 class="section-title">Más opciones</h2>
    <div class="card-soft p-3">
        <form method="post" action="<?= url("/admin/usuarios/{$usuario['id']}/estado") ?>"
              data-confirm="<?= $usuario['activo'] ? '¿Desactivar este usuario? No va a poder ingresar, pero se conserva su historial.' : '¿Volver a activar este usuario?' ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline-<?= $usuario['activo'] ? 'danger' : 'success' ?> w-100" type="submit">
                <i class="bi bi-<?= $usuario['activo'] ? 'person-slash' : 'person-check' ?> me-1"></i>
                <?= $usuario['activo'] ? 'Desactivar usuario' : 'Activar usuario' ?>
            </button>
        </form>
        <form method="post" action="<?= url("/admin/usuarios/{$usuario['id']}/eliminar") ?>" class="mt-2 text-center"
              data-confirm="¿Borrar este usuario? Ya no va a poder entrar; sus visitas se conservan.">
            <?= csrf_field() ?>
            <button class="btn btn-link text-danger" type="submit"><i class="bi bi-trash3"></i> Borrar usuario</button>
        </form>
    </div>
<?php endif; ?>
