<div class="page-head">
    <h1>Usuarios</h1>
    <a class="btn btn-primary" href="<?= url('/admin/usuarios/crear') ?>"><i class="bi bi-person-plus"></i> Nuevo</a>
</div>

<div class="item-list">
    <?php foreach ($usuarios as $u): ?>
        <a class="item-card<?= $u['activo'] ? '' : ' is-inactive' ?>" href="<?= url("/admin/usuarios/{$u['id']}/editar") ?>">
            <div class="item-avatar"><?= e(initials($u)) ?></div>
            <div class="item-body">
                <div class="item-title"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></div>
                <div class="item-sub"><?= e($u['email']) ?></div>
                <div class="item-meta">
                    <span class="badge <?= $u['role_slug'] === 'admin' ? 'badge-soft' : 'badge-muted' ?>"><?= e($u['role_nombre']) ?></span>
                    <?php if ($u['role_slug'] !== 'admin'): ?>
                        <span><i class="bi bi-shop"></i> <?= (int) $u['locales_count'] ?> locales</span>
                    <?php endif; ?>
                    <?php if (!$u['activo']): ?><span class="badge text-bg-secondary">Inactivo</span><?php endif; ?>
                </div>
            </div>
            <i class="bi bi-chevron-right text-body-secondary"></i>
        </a>
    <?php endforeach; ?>
</div>
