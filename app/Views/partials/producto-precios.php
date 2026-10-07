<?php
/**
 * Precios del maestro de Chess. Recibe $producto y opcional $compacto (una sola línea, para la visita).
 * Lista "consumidor final" = la de referencia para el cartel; "base" = la del comercio.
 */
$p = $producto;
if (($p['precio_unidad'] ?? null) === null && ($p['precio_base_unidad'] ?? null) === null) {
    return;
}
$bulto = $p['unidades_bulto'] ? ($p['presentacion_bulto'] ?: 'Bulto') . ' x' . (int) $p['unidades_bulto'] : null;
?>
<?php if (!empty($compacto)): ?>
    <div class="precio-linea mb-2">
        <i class="bi bi-tag"></i>
        <?php if ($p['precio_unidad'] !== null): ?>Unidad <strong><?= precio($p['precio_unidad']) ?></strong><?php endif; ?>
        <?php if ($bulto && $p['precio_bulto'] !== null): ?><span class="text-body-secondary">· <?= e($bulto) ?>: <?= precio($p['precio_bulto']) ?></span><?php endif; ?>
    </div>
<?php else: ?>
    <div class="card-soft p-3 mb-3">
        <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="fw-semibold"><i class="bi bi-tag"></i> Precios</span>
            <?php if ($p['precio_vigente']): ?><span class="small text-body-secondary">desde <?= fecha($p['precio_vigente'], 'd/m/Y') ?></span><?php endif; ?>
        </div>
        <div class="precio-grid">
            <div></div><div class="small text-body-secondary">Unidad</div><div class="small text-body-secondary"><?= e($bulto ?? 'Bulto') ?></div>
            <div class="small">Consumidor final</div>
            <div class="fw-bold"><?= $p['precio_unidad'] !== null ? precio($p['precio_unidad']) : '—' ?></div>
            <div><?= $p['precio_bulto'] !== null ? precio($p['precio_bulto']) : '—' ?></div>
            <div class="small">Base</div>
            <div><?= $p['precio_base_unidad'] !== null ? precio($p['precio_base_unidad']) : '—' ?></div>
            <div><?= $p['precio_base_bulto'] !== null ? precio($p['precio_base_bulto']) : '—' ?></div>
        </div>
        <?php if ($p['codigo_interno']): ?>
            <div class="small text-body-secondary mt-2">Artículo Chess <?= e($p['codigo_interno']) ?><?= $p['descripcion'] ? ' · ' . e($p['descripcion']) : '' ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>
