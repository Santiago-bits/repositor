<?php
use App\Models\Producto;
use App\Models\RelevamientoProducto;

$vid = (int) $visita['id'];
$pid = (int) $producto['id'];
$estadoActual = $rp['estado_stock'] ?? '';
$registrado = $rp && ($rp['stock'] !== null || $rp['estado_stock'] !== null);
?>

<a class="back-link" href="<?= url("/visitas/{$vid}/productos") ?>"><i class="bi bi-arrow-left"></i> Productos de <?= e($visita['local']) ?></a>

<div class="card-soft producto-head mb-2">
    <?php if ($producto['imagen_path']): ?>
        <img class="producto-img" src="<?= url('/productos/' . $pid . '/imagen') ?>" alt="">
    <?php else: ?>
        <div class="producto-img producto-img-vacia"><i class="bi bi-box-seam"></i></div>
    <?php endif; ?>
    <div class="min-w-0">
        <h1 class="h4 fw-bold mb-1"><?= e(Producto::nombreCompleto($producto)) ?></h1>
        <?php if ($producto['marca']): ?><p class="mb-1 text-body-secondary"><?= e($producto['marca']) ?></p><?php endif; ?>
        <div class="item-meta">
            <?php if (!empty($ubicacion['ubicacion_gondola'])): ?><span><i class="bi bi-signpost"></i> <?= e($ubicacion['ubicacion_gondola']) ?></span><?php endif; ?>
            <?php if (isset($ubicacion['stock_habitual'])): ?><span>Habitual: <?= (int) $ubicacion['stock_habitual'] ?></span><?php endif; ?>
            <?php if ($producto['codigo_barras']): ?><span class="font-monospace"><i class="bi bi-upc"></i> <?= e($producto['codigo_barras']) ?></span><?php endif; ?>
        </div>
    </div>
</div>

<?php if ($promo): ?>
    <div class="promo-aviso mb-2">
        <i class="bi bi-tag-fill"></i>
        <span>En promo del finde<?= $promo['precio_promo'] !== null ? ' · ' . precio($promo['precio_promo']) : '' ?></span>
    </div>
<?php else: ?>
    <a class="small d-inline-block mb-2" href="<?= url("/visitas/{$vid}/promociones/crear?producto={$pid}") ?>"><i class="bi bi-megaphone"></i> ¿Está en promo? Registrala</a>
<?php endif; ?>

<?php if ($anterior): ?>
    <p class="small text-body-secondary mb-3">
        <i class="bi bi-clock-history"></i> Última vez (<?= fecha($anterior['fecha'], 'd/m') ?>):
        <strong><?= $anterior['stock'] !== null ? (int) $anterior['stock'] : '—' ?></strong>
        <?php if ($anterior['estado_stock']): ?>· <?= RelevamientoProducto::ESTADOS[$anterior['estado_stock']][0] ?><?php endif; ?>
    </p>
<?php endif; ?>

<!-- Vencimientos -->
<section class="card-soft p-3 mb-3">
    <h2 class="card-title-sm"><i class="bi bi-calendar-event"></i> Vencimientos</h2>
    <div id="vencimientos"><?= partial('vencimientos-lista', ['vencimientos' => $vencimientos, 'visitaId' => $vid, 'editable' => true]) ?></div>

    <form class="venc-form mt-2" method="post" action="<?= url("/visitas/{$vid}/productos/{$pid}/vencimientos") ?>" data-venc-form data-destino="#vencimientos">
        <?= csrf_field() ?>
        <input class="form-control" type="date" name="fecha" required aria-label="Fecha de vencimiento">
        <input class="form-control" type="number" name="cantidad" min="0" max="99999" inputmode="numeric" placeholder="Cant." aria-label="Cantidad">
        <button class="btn btn-primary" type="submit" aria-label="Agregar vencimiento"><i class="bi bi-plus-lg"></i></button>
        <input class="form-control venc-nota" name="nota" maxlength="120" placeholder="Ubicación / nota (ej: depósito)" aria-label="Nota">
    </form>

    <?php if ($lotesAnteriores !== []): ?>
        <div class="lotes-anteriores mt-3">
            <div class="small text-body-secondary mb-1">La vez anterior:</div>
            <div class="small mb-2">
                <?php foreach ($lotesAnteriores as $l): ?>
                    <span class="me-2"><?= fecha($l['fecha_vencimiento']) ?> → <?= $l['cantidad'] !== null ? (int) $l['cantidad'] . ' u.' : '—' ?></span>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    data-accion="<?= url("/visitas/{$vid}/productos/{$pid}/vencimientos/copiar") ?>" data-destino="#vencimientos">
                <i class="bi bi-copy"></i> Copiar esos lotes
            </button>
        </div>
    <?php endif; ?>
</section>

<!-- Stock -->
<form class="card-soft p-3 mb-3" method="post" action="<?= url("/visitas/{$vid}/productos/{$pid}/stock") ?>" data-stock-form>
    <?= csrf_field() ?>
    <label class="form-label fw-semibold" for="stock">Stock encontrado <span class="fw-normal text-body-secondary small">(opcional)</span></label>
    <div class="stepper">
        <button type="button" data-paso="-1" aria-label="Restar uno"><i class="bi bi-dash-lg"></i></button>
        <input id="stock" name="stock" type="number" inputmode="numeric" pattern="[0-9]*" min="0" max="99999"
               value="<?= e($rp['stock'] ?? '') ?>" placeholder="0">
        <button type="button" data-paso="1" aria-label="Sumar uno"><i class="bi bi-plus-lg"></i></button>
    </div>

    <div class="estado-chips mt-3" role="radiogroup" aria-label="Estado del stock">
        <?php foreach (RelevamientoProducto::ESTADOS as $valor => [$etiqueta]): ?>
            <input type="radio" class="btn-check" name="estado_stock" id="estado-<?= $valor ?>" value="<?= $valor ?>" <?= $estadoActual === $valor ? 'checked' : '' ?>>
            <label class="btn estado-chip estado-<?= $valor ?>" for="estado-<?= $valor ?>"><?= $etiqueta ?></label>
        <?php endforeach; ?>
    </div>

    <div class="form-check form-switch mt-3">
        <input class="form-check-input" type="checkbox" role="switch" id="con_problema" name="con_problema" value="1" <?= !empty($rp['con_problema']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="con_problema">Producto con problema</label>
    </div>

    <div class="d-grid gap-2 mt-3">
        <button class="btn btn-primary btn-xl" type="submit" name="siguiente" value="1">GUARDAR Y SIGUIENTE <i class="bi bi-arrow-right ms-1"></i></button>
        <button class="btn btn-outline-primary" type="submit" name="siguiente" value="0">Guardar</button>
    </div>
    <p class="small text-success text-center mb-0 mt-2" data-guardado><?= $registrado ? '✅ Guardado ' . fecha($rp['updated_at'] ?? $rp['created_at'], 'H:i') : '' ?></p>
</form>

<?php if ($rp): ?>
    <form method="post" action="<?= url("/visitas/{$vid}/productos/{$pid}/quitar") ?>" class="text-center mb-3"
          data-confirm="¿Quitar este producto de la visita? Se borran su stock y sus vencimientos de hoy.">
        <?= csrf_field() ?>
        <button class="btn btn-link btn-sm text-danger" type="submit"><i class="bi bi-trash3"></i> Quitar de esta visita</button>
    </form>
<?php endif; ?>

<!-- Fotos -->
<section class="card-soft p-3 mb-3">
    <h2 class="card-title-sm"><i class="bi bi-camera"></i> Fotos</h2>
    <?= partial('foto-subir', ['visitaId' => $vid, 'productoId' => $pid]) ?>
    <div id="fotos-lista" class="mt-2"><?= partial('fotos-grid', ['fotos' => $fotos, 'editable' => true, 'mostrarProducto' => false, 'productoId' => $pid]) ?></div>
</section>

<!-- Observaciones -->
<section class="card-soft p-3 mb-3">
    <h2 class="card-title-sm"><i class="bi bi-chat-left-text"></i> Observaciones</h2>
    <?= partial('observacion-form', ['visitaId' => $vid, 'productoId' => $pid]) ?>
    <div id="observaciones-lista" class="mt-2"><?= partial('observaciones-lista', ['observaciones' => $observaciones, 'editable' => true, 'mostrarProducto' => false, 'productoId' => $pid]) ?></div>
</section>
