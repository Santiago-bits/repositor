<?php
use App\Models\Promocion;

$vacio = [
    'vigentes'   => 'No hay promociones vigentes hoy.',
    'proximas'   => 'No hay promociones programadas para más adelante.',
    'terminadas' => 'Todavía no terminó ninguna promoción.',
    'todas'      => 'Todavía no cargaste promociones.',
][$filtro];
$qsLocal = $localId ? '&local_id=' . $localId : '';
?>
<div class="page-head">
    <h1>Promociones</h1>
    <p class="page-sub">Las que cargás acá las ven todos los repositores en sus locales.</p>
    <a class="btn btn-primary btn-pill" href="<?= url('/admin/promociones/crear') ?>"><i class="bi bi-plus-lg me-1"></i> Nueva promoción</a>
</div>

<nav class="segmentos mb-2" aria-label="Filtrar promociones">
    <?php foreach ($filtros as $clave => $texto): ?>
        <a class="<?= $filtro === $clave ? 'active' : '' ?>" href="<?= url('/admin/promociones?filtro=' . $clave . $qsLocal) ?>">
            <?= $texto ?> <span class="segmentos-n"><?= (int) $cuenta[$clave] ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form method="get" action="<?= url('/admin/promociones') ?>" class="mb-3">
    <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
    <div class="select-icono">
        <i class="bi bi-shop"></i>
        <select class="form-select" name="local_id" onchange="this.form.submit()" aria-label="Filtrar por local">
            <option value="">Todos los locales</option>
            <?php foreach ($locales as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $localId === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($promociones === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-megaphone"></i>
        <p class="fw-semibold text-body mb-1"><?= e($vacio) ?></p>
        <p class="small mb-3">Cargá una promo con el producto, las fechas, el precio y los locales donde vale.</p>
        <a class="btn btn-outline-primary btn-pill" href="<?= url('/admin/promociones/crear') ?>"><i class="bi bi-plus-lg me-1"></i> Crear promoción</a>
    </div>
<?php else: ?>
    <div class="promo-lista">
        <?php foreach ($promociones as $p): ?>
            <?php [$sit, $clase] = Promocion::situacion($p); ?>
            <a class="promo-card card-soft <?= str_replace('text-bg-', 'promo-', $clase) ?>" href="<?= url('/admin/promociones/' . $p['id'] . '/editar') ?>">
                <?= producto_thumb($p) ?>
                <div class="min-w-0 flex-grow-1">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="fw-semibold text-truncate"><?= e(trim($p['nombre'] . ' ' . $p['presentacion'])) ?></div>
                        <span class="badge <?= $clase ?>"><?= $sit ?></span>
                    </div>
                    <div class="promo-fechas">
                        <i class="bi bi-calendar3"></i> <?= fecha($p['fecha_inicio'], 'd/m') ?> al <?= fecha($p['fecha_fin'], 'd/m') ?>
                        <span class="promo-cuando">· <?= e(Promocion::cuando($p)) ?></span>
                    </div>
                    <?php if ($p['precio_promo'] !== null): ?>
                        <div class="promo-precio">
                            <?= precio($p['precio_promo']) ?>
                            <?php if ($p['precio_normal'] !== null): ?><s><?= precio($p['precio_normal']) ?></s><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="promo-pie">
                        <span><i class="bi bi-shop"></i> <?= e($p['locales'] ?: 'Sin locales') ?></span>
                        <?php if ($p['creador']): ?><span><i class="bi bi-person"></i> <?= e($p['creador']) ?></span><?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
