<?php
use App\Models\Producto;

// Dentro de una visita, los resultados llevan a registrar el producto en vez de a su ficha.
$enlace ??= fn (array $p) => url('/productos/' . $p['id']);
$crearExtra ??= '';
?>
<?php if ($q === ''): ?>
    <div class="empty-state">
        <i class="bi bi-search"></i>
        <p class="mb-0">Escribí el nombre, la marca o el código.<br>O tocá <strong>Escanear</strong> para usar la cámara.</p>
    </div>
<?php elseif ($resultados === []): ?>
    <?php $esCodigo = (bool) preg_match('/^\d{6,}$/', $q); ?>
    <div class="empty-state card-soft">
        <i class="bi bi-emoji-neutral"></i>
        <p>No encontramos «<?= e($q) ?>».</p>
        <a class="btn btn-primary btn-xl" href="<?= url('/productos/crear?' . ($esCodigo ? 'codigo=' : 'nombre=') . rawurlencode($q) . $crearExtra) ?>">
            <i class="bi bi-plus-lg me-1"></i> Crear producto
        </a>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($resultados as $p): ?>
            <a class="item-card<?= $p['activo'] ? '' : ' is-inactive' ?>" href="<?= e($enlace($p)) ?>">
                <?php if ($p['imagen_path']): ?>
                    <img class="item-thumb" src="<?= url('/productos/' . $p['id'] . '/imagen') ?>" alt="" loading="lazy">
                <?php else: ?>
                    <div class="item-icon"><i class="bi bi-box-seam"></i></div>
                <?php endif; ?>
                <div class="item-body">
                    <div class="item-title"><?= e(Producto::nombreCompleto($p)) ?></div>
                    <div class="item-sub"><?= e(implode(' · ', array_filter([$p['marca'], Producto::categoriaCompleta($p)]))) ?: '&nbsp;' ?></div>
                    <?php if ($p['codigo_barras'] || !$p['activo']): ?>
                        <div class="item-meta">
                            <?php if ($p['codigo_barras']): ?><span class="font-monospace"><i class="bi bi-upc"></i> <?= e($p['codigo_barras']) ?></span><?php endif; ?>
                            <?php if (!$p['activo']): ?><span class="badge text-bg-secondary">Inactivo</span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if (count($resultados) >= 20): ?>
        <p class="text-center text-body-secondary small mt-3 mb-0">Mostrando los primeros 20. Escribí algo más para afinar.</p>
    <?php endif; ?>
<?php endif; ?>
