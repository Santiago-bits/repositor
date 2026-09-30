<?php use App\Models\Producto; ?>

<div class="page-head">
    <h1>Productos</h1>
    <a class="btn btn-primary" href="<?= url('/admin/productos/crear') ?>"><i class="bi bi-plus-lg"></i> Nuevo</a>
</div>

<form method="get" action="<?= url('/admin/productos') ?>" class="mb-3" role="search">
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input class="form-control" type="search" name="q" value="<?= e($buscar) ?>" placeholder="Nombre, marca, código o categoría">
    </div>
</form>

<p class="text-body-secondary small"><?= count($productos) ?> producto<?= count($productos) === 1 ? '' : 's' ?></p>

<?php if ($productos === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-box-seam"></i>
        <p class="mb-0"><?= $buscar !== '' ? 'No hay productos que coincidan.' : 'Todavía no hay productos cargados.' ?></p>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($productos as $p): ?>
            <a class="item-card<?= $p['activo'] ? '' : ' is-inactive' ?>" href="<?= url("/admin/productos/{$p['id']}/editar") ?>">
                <?php if ($p['imagen_path']): ?>
                    <img class="item-thumb" src="<?= url('/productos/' . $p['id'] . '/imagen') ?>" alt="" loading="lazy">
                <?php else: ?>
                    <div class="item-icon"><i class="bi bi-box-seam"></i></div>
                <?php endif; ?>
                <div class="item-body">
                    <div class="item-title"><?= e(Producto::nombreCompleto($p)) ?></div>
                    <div class="item-sub"><?= e(implode(' · ', array_filter([$p['marca'], Producto::categoriaCompleta($p)]))) ?: '&nbsp;' ?></div>
                    <div class="item-meta">
                        <?php if ($p['codigo_barras']): ?>
                            <span class="font-monospace"><i class="bi bi-upc"></i> <?= e($p['codigo_barras']) ?></span>
                        <?php else: ?>
                            <span class="text-warning-emphasis"><i class="bi bi-upc"></i> Sin código</span>
                        <?php endif; ?>
                        <?php if (!$p['activo']): ?><span class="badge text-bg-secondary">Inactivo</span><?php endif; ?>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
