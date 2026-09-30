<?php use App\Models\Promocion; ?>
<div class="page-head">
    <h1>Promociones</h1>
    <a class="btn btn-primary" href="<?= url('/admin/promociones/crear') ?>"><i class="bi bi-plus-lg"></i> Nueva</a>
</div>

<nav class="nav nav-pills filtro-pills mb-2">
    <?php foreach ($filtros as $clave => $texto): ?>
        <a class="nav-link<?= $filtro === $clave ? ' active' : '' ?>" href="<?= url('/admin/promociones?filtro=' . $clave . ($localId ? '&local_id=' . $localId : '')) ?>"><?= $texto ?></a>
    <?php endforeach; ?>
</nav>

<form method="get" action="<?= url('/admin/promociones') ?>" class="mb-3">
    <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
    <select class="form-select" name="local_id" onchange="this.form.submit()" aria-label="Filtrar por local">
        <option value="">Todos los locales</option>
        <?php foreach ($locales as $l): ?>
            <option value="<?= $l['id'] ?>" <?= $localId === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($promociones === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-megaphone"></i>
        <p class="mb-0">No hay promociones en esta vista.</p>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($promociones as $p): ?>
            <?php [$sit, $clase] = Promocion::situacion($p); ?>
            <a class="item-card" href="<?= url('/admin/promociones/' . $p['id'] . '/editar') ?>">
                <div class="item-icon"><i class="bi bi-megaphone"></i></div>
                <div class="item-body">
                    <div class="item-title"><?= e(trim($p['nombre'] . ' ' . $p['presentacion'])) ?></div>
                    <div class="item-sub"><?= fecha($p['fecha_inicio'], 'd/m') ?> al <?= fecha($p['fecha_fin'], 'd/m/Y') ?> · <?= e($p['locales'] ?: 'Sin locales') ?></div>
                    <div class="item-meta">
                        <?php if ($p['precio_promo'] !== null): ?><span><i class="bi bi-tag"></i> <?= precio($p['precio_promo']) ?></span><?php endif; ?>
                        <?php if ($p['creador']): ?><span><i class="bi bi-person"></i> <?= e($p['creador']) ?></span><?php endif; ?>
                    </div>
                </div>
                <span class="badge <?= $clase ?>"><?= $sit ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
