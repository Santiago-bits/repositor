<?php
use App\Models\Producto;

$estados = [
    'normal'      => ['Normal', 'text-bg-success'],
    'bajo'        => ['Bajo stock', 'text-bg-warning'],
    'sin_stock'   => ['Sin stock', 'text-bg-danger'],
    'no_exhibido' => ['No exhibido', 'text-bg-secondary'],
];
?>

<a class="back-link" href="<?= url('/productos') ?>" data-volver><i class="bi bi-arrow-left"></i> Productos</a>

<div class="card-soft producto-head mb-3">
    <?php if ($producto['imagen_path']): ?>
        <img class="producto-img" src="<?= url('/productos/' . $producto['id'] . '/imagen') ?>" alt="">
    <?php else: ?>
        <div class="producto-img producto-img-vacia"><i class="bi bi-box-seam"></i></div>
    <?php endif; ?>
    <div class="min-w-0">
        <h1 class="h4 fw-bold mb-1"><?= e(Producto::nombreCompleto($producto)) ?></h1>
        <?php if ($producto['marca']): ?><p class="mb-1 text-body-secondary"><?= e($producto['marca']) ?></p><?php endif; ?>
        <div class="item-meta">
            <?php if ($cat = Producto::categoriaCompleta($producto)): ?><span><i class="bi bi-tag"></i> <?= e($cat) ?></span><?php endif; ?>
            <?php if ($producto['codigo_barras']): ?><span class="font-monospace"><i class="bi bi-upc"></i> <?= e($producto['codigo_barras']) ?></span><?php endif; ?>
            <?php if (!$producto['activo']): ?><span class="badge text-bg-secondary">Inactivo</span><?php endif; ?>
        </div>
    </div>
</div>

<?= partial('producto-precios', ['producto' => $producto]) ?>

<?php // Los de Chess muestran su descripción original junto a los precios. ?>
<?php if ($producto['descripcion'] && !$producto['codigo_interno']): ?>
    <p class="text-body-secondary"><?= nl2br(e($producto['descripcion'])) ?></p>
<?php endif; ?>

<?php if (is_admin()): ?>
    <a class="btn btn-outline-primary w-100" href="<?= url('/admin/productos/' . $producto['id'] . '/editar') ?>"><i class="bi bi-pencil"></i> Editar producto</a>
<?php endif; ?>

<h2 class="section-title">Historial de stock</h2>
<?php if ($historial === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-graph-up"></i>
        <p class="mb-0">Todavía no hay registros. Se completa solo cuando cargues stock en una visita.</p>
    </div>
<?php else: ?>
    <div class="card-soft">
        <?php foreach ($historial as $h): ?>
            <div class="historial-row">
                <div>
                    <div class="fw-semibold"><?= fecha($h['fecha'], 'd/m') ?> · <?= e($h['local']) ?></div>
                    <?php if ($h['estado_stock']): ?>
                        <span class="badge <?= $estados[$h['estado_stock']][1] ?>"><?= $estados[$h['estado_stock']][0] ?></span>
                    <?php endif; ?>
                </div>
                <div class="historial-stock"><?= $h['stock'] !== null ? (int) $h['stock'] : '—' ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
