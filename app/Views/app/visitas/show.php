<?php
use App\Models\Local;
use App\Models\Producto;
use App\Models\RelevamientoProducto;
use App\Services\VencimientoService;

// Las visitas de hoy siempre se pueden tocar (si se cerró sola al irte, se reabre al cargar algo).
$editable = $propia && ($visita['estado'] === 'en_proceso' || ($visita['estado'] === 'finalizado' && $visita['fecha'] === date('Y-m-d')));
$vid = (int) $visita['id'];
?>

<a class="back-link" href="<?= url('/') ?>" data-volver><i class="bi bi-arrow-left"></i> Volver</a>

<div class="card-soft visita-head mb-3">
    <div class="d-flex align-items-center gap-3">
        <div class="item-icon"><i class="bi <?= Local::ICONOS[$visita['tipo']] ?>"></i></div>
        <div class="min-w-0 flex-grow-1">
            <h1 class="h4 fw-bold mb-0 text-truncate"><?= e($visita['local']) ?></h1>
            <?php if ($visita['direccion']): ?><div class="small text-body-secondary text-truncate"><?= e($visita['direccion']) ?></div><?php endif; ?>
        </div>
    </div>

    <?php if (!$propia): ?>
        <p class="small text-body-secondary mb-0 mt-2"><i class="bi bi-person"></i> <?= e($visita['usuario']) ?> · <?= fecha($visita['fecha']) ?></p>
    <?php elseif ($visita['fecha'] !== date('Y-m-d')): ?>
        <p class="small text-body-secondary mb-0 mt-2"><i class="bi bi-calendar"></i> <?= fecha($visita['fecha']) ?></p>
    <?php endif; ?>
</div>

<?php if ($editable || $cortasLocal !== []): ?>
    <h2 class="section-title mt-0">Vence primero en este local</h2>
    <?php if ($cortasLocal === []): ?>
        <p class="small text-body-secondary"><i class="bi bi-check-circle text-success"></i> No hay fechas cortas anotadas acá. Cargalas con «Vencimientos».</p>
    <?php else: ?>
        <p class="small text-body-secondary mb-2">Ponelos adelante en la heladera. Si ya los sacaste, tocá Retirar.</p>
        <div class="card-soft mb-3">
            <?php foreach (array_slice($cortasLocal, 0, 8) as $v): ?>
                <?= partial('fecha-corta', ['v' => $v, 'conLocal' => false]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($editable): ?>
    <div class="accion-grid mb-2" data-foto-subir
         data-endpoint="<?= url("/visitas/{$vid}/fotos") ?>" data-producto="" data-scope="visita"
         data-destino="#fotos-lista" data-estado="#foto-estado">
        <a class="accion accion-principal" href="<?= url("/visitas/{$vid}/vencimientos") ?>">
            <i class="bi bi-calendar-event"></i><span>Vencimientos</span>
        </a>
        <a class="accion" href="<?= url("/visitas/{$vid}/promociones/crear") ?>">
            <i class="bi bi-megaphone"></i><span>Promos del finde</span>
        </a>
        <a class="accion" href="<?= url("/visitas/{$vid}/faltantes") ?>">
            <i class="bi bi-cart-x"></i><span>Faltantes</span>
        </a>
        <label class="accion">
            <i class="bi bi-camera"></i><span>Sacar foto</span>
            <input type="file" accept="image/*" capture="environment" hidden data-foto-input>
        </label>
        <a class="accion" href="#observaciones">
            <i class="bi bi-chat-left-text"></i><span>Observación</span>
        </a>
        <a class="accion" href="<?= url("/visitas/{$vid}/mensaje?tipo=faltantes") ?>">
            <i class="bi bi-whatsapp"></i><span>Lista vendedor</span>
        </a>
    </div>
    <div id="foto-estado"></div>
<?php endif; ?>

<?php if ($promos !== []): ?>
    <h2 class="section-title" id="promociones">Promos vigentes acá</h2>
    <div class="card-soft mb-2">
        <?php foreach ($promos as $p): ?>
            <div class="historial-row">
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate"><i class="bi bi-tag text-primary"></i> <?= e(trim($p['nombre'] . ' ' . $p['presentacion'])) ?></div>
                    <?php if ($p['observaciones']): ?><div class="small text-body-secondary"><?= e($p['observaciones']) ?></div><?php endif; ?>
                </div>
                <?php if ($p['precio_promo'] !== null): ?><div class="fw-bold"><?= precio($p['precio_promo']) ?></div><?php endif; ?>
                <?php if ($editable && ((int) $p['created_by'] === (int) auth()['id'] || is_admin())): ?>
                    <form method="post" action="<?= url("/visitas/{$vid}/promociones/{$p['id']}/eliminar") ?>" data-confirm="¿Borrar esta promoción?">
                        <?= csrf_field() ?>
                        <button class="btn-icon" type="submit" aria-label="Borrar promoción"><i class="bi bi-trash3"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($registrados !== []): ?>
    <h2 class="section-title" id="registrados">
        Registrado en esta visita <span class="text-lowercase">(<?= count($registrados) ?> producto<?= count($registrados) === 1 ? '' : 's' ?>)</span>
    </h2>
    <div class="card-soft">
        <?php foreach ($registrados as $r): ?>
            <?php
            $tag = $editable ? 'a' : 'div';
            $susLotes = $lotes[(int) $r['producto_id']] ?? [];
            ?>
            <<?= $tag ?> class="registro-row text-reset text-decoration-none"<?= $editable ? ' href="' . url("/visitas/{$vid}/productos/{$r['producto_id']}") . '"' : '' ?>>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold text-truncate"><?= e(Producto::nombreCompleto($r)) ?></div>

                    <div class="registro-datos">
                        <?php if ($r['stock'] !== null): ?>
                            <span><i class="bi bi-box-seam"></i> Stock <strong><?= (int) $r['stock'] ?> u.</strong></span>
                        <?php endif; ?>
                        <?php if ($r['estado_stock'] && $r['estado_stock'] !== 'normal'): ?>
                            <span class="badge <?= RelevamientoProducto::ESTADOS[$r['estado_stock']][1] ?>"><?= RelevamientoProducto::ESTADOS[$r['estado_stock']][0] ?></span>
                        <?php endif; ?>
                        <?php if (isset($promos[(int) $r['producto_id']])): ?>
                            <span class="text-primary"><i class="bi bi-megaphone"></i> En promo</span>
                        <?php endif; ?>
                        <?php if ($r['con_problema']): ?>
                            <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Con problema</span>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($susLotes as $v): ?>
                        <?php $estado = VencimientoService::estado($v['fecha_vencimiento']); ?>
                        <div class="registro-lote">
                            <i class="bi bi-calendar-event"></i>
                            Vence <strong><?= fecha($v['fecha_vencimiento'], 'd/m') ?></strong>
                            <?php if ($v['retirado_at']): ?>
                                <span class="badge text-bg-secondary">Retirado</span>
                            <?php else: ?>
                                <span class="venc-badge <?= $estado['clase'] ?>"><?= e($estado['etiqueta']) ?></span>
                            <?php endif; ?>
                            <?php if ($v['cantidad'] !== null): ?>· <?= (int) $v['cantidad'] ?> u.<?php endif; ?>
                            <?php if ($v['nota']): ?>· <?= e($v['nota']) ?><?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($r['stock'] === null && $susLotes === [] && !$r['estado_stock']): ?>
                        <div class="small text-body-secondary">Sin datos cargados</div>
                    <?php endif; ?>
                </div>
                <?php if ($editable): ?><i class="bi bi-chevron-right text-body-secondary"></i><?php endif; ?>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($editable || $fotos !== []): ?>
    <h2 class="section-title" id="fotos">Fotos</h2>
    <div id="fotos-lista"><?= partial('fotos-grid', ['fotos' => $fotos, 'editable' => $editable, 'mostrarProducto' => true, 'productoId' => null]) ?></div>
<?php endif; ?>

<?php if ($editable || $observaciones !== []): ?>
    <h2 class="section-title" id="observaciones">Observaciones</h2>
    <?php if ($editable): ?>
        <div class="card-soft p-3 mb-2"><?= partial('observacion-form', ['visitaId' => $vid, 'productoId' => null]) ?></div>
    <?php endif; ?>
    <div id="observaciones-lista"><?= partial('observaciones-lista', ['observaciones' => $observaciones, 'editable' => $editable, 'mostrarProducto' => true, 'productoId' => null]) ?></div>
<?php endif; ?>

<?php if ($propia): ?>
    <a class="btn btn-outline-primary w-100 mt-4" href="<?= url('/mensaje?fecha=' . $visita['fecha']) ?>"><i class="bi bi-chat-square-text me-1"></i> Mensaje del día para el supervisor</a>
<?php endif; ?>

<form method="post" action="<?= url("/visitas/{$vid}/eliminar") ?>" class="mt-3 text-center"
      data-confirm="¿Eliminar esta visita? Se borra todo lo cargado (stock, vencimientos, fotos y observaciones). No se puede deshacer.">
    <?= csrf_field() ?>
    <button class="btn btn-link text-danger" type="submit"><i class="bi bi-trash3"></i> Eliminar visita</button>
</form>
