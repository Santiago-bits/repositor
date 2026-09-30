<?php
use App\Models\Local;
use App\Models\Producto;
use App\Models\Relevamiento;
use App\Models\RelevamientoProducto;

$abierta = $visita['estado'] === 'en_proceso';
$editable = $abierta && $propia;
$vid = (int) $visita['id'];
[$estadoTexto, $estadoClase] = Relevamiento::ESTADOS[$visita['estado']];
?>

<a class="back-link" href="<?= url(is_admin() && !$propia ? '/admin/relevamientos' : '/') ?>" data-volver><i class="bi bi-arrow-left"></i> Volver</a>

<div class="card-soft visita-head mb-3">
    <div class="d-flex align-items-center gap-3">
        <div class="item-icon"><i class="bi <?= Local::ICONOS[$visita['tipo']] ?>"></i></div>
        <div class="min-w-0 flex-grow-1">
            <h1 class="h4 fw-bold mb-0 text-truncate"><?= e($visita['local']) ?></h1>
            <?php if ($visita['direccion']): ?><div class="small text-body-secondary text-truncate"><?= e($visita['direccion']) ?></div><?php endif; ?>
        </div>
        <span class="badge <?= $estadoClase ?>"><?= $estadoTexto ?></span>
    </div>

    <?php if (!$propia): ?>
        <p class="small text-body-secondary mb-0 mt-2"><i class="bi bi-person"></i> <?= e($visita['usuario']) ?> · <?= fecha($visita['fecha']) ?></p>
    <?php elseif ($visita['fecha'] !== date('Y-m-d')): ?>
        <p class="small text-body-secondary mb-0 mt-2"><i class="bi bi-calendar"></i> <?= fecha($visita['fecha']) ?></p>
    <?php endif; ?>
</div>

<?php if ($editable): ?>
    <div class="accion-grid mb-2" data-foto-subir
         data-endpoint="<?= url("/visitas/{$vid}/fotos") ?>" data-producto="" data-scope="visita"
         data-destino="#fotos-lista" data-estado="#foto-estado">
        <a class="accion accion-principal" href="<?= url("/visitas/{$vid}/productos") ?>">
            <i class="bi bi-box-seam"></i><span>Registrar productos</span>
        </a>
        <label class="accion">
            <i class="bi bi-camera"></i><span>Sacar foto</span>
            <input type="file" accept="image/*" capture="environment" hidden data-foto-input>
        </label>
        <a class="accion" href="#observaciones">
            <i class="bi bi-chat-left-text"></i><span>Observación</span>
        </a>
    </div>
    <div id="foto-estado"></div>
<?php endif; ?>

<?php $sinContar = count(array_filter($conteo, fn ($i) => $i['rp_id'] === null)); ?>

<?php if ($tareas !== []): ?>
    <h2 class="section-title">Tareas de <?= $visita['fecha'] === date('Y-m-d') ? 'hoy' : 'ese día' ?></h2>
    <div id="tareas-lista">
        <?= partial('tareas-visita', ['visita' => $visita, 'tareas' => $tareas, 'editable' => $editable, 'pendientes' => $sinContar]) ?>
    </div>
<?php endif; ?>

<?php if ($editable || $promos !== [] || $conteo !== []): ?>
    <h2 class="section-title" id="promociones">Promociones</h2>

    <?php if ($editable && $sinContar > 0): ?>
        <a class="conteo-cta mb-2" href="<?= url("/visitas/{$vid}/conteo") ?>">
            <i class="bi bi-megaphone-fill"></i>
            <span class="flex-grow-1">
                <strong>Conteo de promociones</strong><br>
                <span class="small"><?= $sinContar ?> producto<?= $sinContar > 1 ? 's' : '' ?> para contar</span>
            </span>
            <span class="btn btn-light btn-sm fw-bold">CONTAR</span>
        </a>
    <?php elseif ($conteo !== []): ?>
        <a class="conteo-hecho mb-2" href="<?= url("/visitas/{$vid}/conteo") ?>">
            <i class="bi bi-check-circle-fill text-success"></i> Conteo completado (<?= count($conteo) ?>/<?= count($conteo) ?>)
        </a>
    <?php endif; ?>

    <?php if ($promos !== []): ?>
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

    <?php if ($editable): ?>
        <a class="btn btn-primary btn-xl w-100" href="<?= url("/visitas/{$vid}/promociones/crear") ?>"><i class="bi bi-megaphone me-1"></i> Promos del finde</a>
    <?php endif; ?>
<?php endif; ?>

<?php if ($registrados !== []): ?>
    <h2 class="section-title" id="registrados">Registrado en esta visita</h2>
    <div class="card-soft">
        <?php foreach ($registrados as $r): ?>
            <?php $tag = $editable ? 'a' : 'div'; ?>
            <<?= $tag ?> class="historial-row text-reset text-decoration-none"<?= $editable ? ' href="' . url("/visitas/{$vid}/productos/{$r['producto_id']}") . '"' : '' ?>>
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate"><?= e(Producto::nombreCompleto($r)) ?></div>
                    <div class="d-flex flex-wrap gap-1 align-items-center small text-body-secondary">
                        <?php if ($r['estado_stock']): ?>
                            <span class="badge <?= RelevamientoProducto::ESTADOS[$r['estado_stock']][1] ?>"><?= RelevamientoProducto::ESTADOS[$r['estado_stock']][0] ?></span>
                        <?php endif; ?>
                        <?php if ($r['vencimientos'] > 0): ?><span><i class="bi bi-calendar-event"></i> <?= (int) $r['vencimientos'] ?></span><?php endif; ?>
                        <?php if ($r['con_problema']): ?><span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Con problema</span><?php endif; ?>
                    </div>
                </div>
                <div class="historial-stock"><?= $r['stock'] !== null ? (int) $r['stock'] : '—' ?></div>
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

<?php if ($editable): ?>
    <form method="post" action="<?= url("/visitas/{$vid}/finalizar") ?>" class="mt-4"
          data-confirm="¿Finalizar la visita a <?= e($visita['local']) ?>?">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-xl w-100" type="submit"><i class="bi bi-flag-fill me-1"></i> Finalizar visita</button>
    </form>

<?php elseif ($visita['estado'] === 'finalizado' && $propia): ?>
    <div class="d-grid gap-2 mt-4">
        <a class="btn btn-primary btn-xl" href="<?= url('/mensaje?fecha=' . $visita['fecha']) ?>"><i class="bi bi-chat-square-text me-1"></i> Mensaje del día para el supervisor</a>
        <a class="btn btn-outline-primary" href="<?= url('/') ?>"><i class="bi bi-house-door me-1"></i> Volver al inicio</a>
    </div>
<?php endif; ?>

<form method="post" action="<?= url("/visitas/{$vid}/eliminar") ?>" class="mt-3 text-center"
      data-confirm="¿Eliminar esta visita? Se borra todo lo cargado (stock, vencimientos, fotos y observaciones). No se puede deshacer.">
    <?= csrf_field() ?>
    <button class="btn btn-link text-danger" type="submit"><i class="bi bi-trash3"></i> Eliminar visita</button>
</form>
