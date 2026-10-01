<?php
use App\Models\Producto;
use App\Services\VencimientoService;

$vid = (int) $visita['id'];
?>
<a class="back-link" href="<?= url("/visitas/{$vid}") ?>"><i class="bi bi-arrow-left"></i> Volver a la visita</a>

<div class="page-head">
    <div class="min-w-0">
        <h1>Vencimientos</h1>
        <div class="small text-body-secondary text-truncate"><i class="bi bi-geo-alt"></i> <?= e($visita['local']) ?></div>
    </div>
</div>

<form method="post" action="<?= url("/visitas/{$vid}/vencimientos") ?>" data-vencimientos>
    <?= csrf_field() ?>

    <div class="search-input mb-3">
        <i class="bi bi-search"></i>
        <input type="search" placeholder="Buscar bebida…" autocomplete="off" autocapitalize="off" enterkeyhint="search"
               aria-label="Buscar producto" data-venc-buscar>
    </div>

    <?php // Resultados: solo aparecen mientras se escribe. Tocar uno lo agrega abajo (se puede repetir: otro lote). ?>
    <div class="card-soft promo-grupo mb-3" data-venc-resultados hidden>
        <?php foreach ($productos as $p): ?>
            <button type="button" class="venc-opcion" data-id="<?= (int) $p['id'] ?>"
                    data-nombre="<?= e(Producto::nombreCompleto($p)) ?>"
                    data-texto="<?= e(mb_strtolower(Producto::nombreCompleto($p) . ' ' . $p['marca'])) ?>">
                <span class="min-w-0 flex-grow-1 text-start">
                    <span class="d-block fw-semibold"><?= e(Producto::nombreCompleto($p)) ?></span>
                    <?php if ($p['marca']): ?><span class="small text-body-secondary"><?= e($p['marca']) ?></span><?php endif; ?>
                </span>
                <i class="bi bi-plus-circle text-primary fs-5"></i>
            </button>
        <?php endforeach; ?>
    </div>
    <p class="small text-body-secondary" data-venc-sin-resultados hidden>No hay productos con ese nombre.</p>

    <h2 class="section-title mt-0">Para guardar (<span data-venc-contador>0</span>)</h2>
    <div data-venc-nuevos></div>
    <p class="small text-body-secondary" data-venc-vacio>Buscá el producto arriba y tocalo para ponerle la fecha.</p>

    <div class="form-actions" data-venc-acciones hidden>
        <button class="btn btn-primary btn-xl w-100" type="submit"><i class="bi bi-check-lg me-1"></i> GUARDAR</button>
    </div>

    <template data-venc-plantilla>
        <div class="card-soft venc-nuevo mb-2">
            <div class="d-flex align-items-start gap-2 mb-2">
                <div class="fw-semibold flex-grow-1 min-w-0" data-venc-nombre></div>
                <button type="button" class="btn-icon" aria-label="Quitar" data-venc-quitar><i class="bi bi-x-lg"></i></button>
            </div>
            <input type="hidden" data-campo="producto">
            <div class="row g-2">
                <div class="col-7">
                    <label class="form-label small mb-1">Vence</label>
                    <input class="form-control" type="date" data-campo="fecha" required>
                </div>
                <div class="col-5">
                    <label class="form-label small mb-1">Cantidad</label>
                    <input class="form-control" type="number" min="0" max="99999" inputmode="numeric" placeholder="—" data-campo="cantidad">
                </div>
                <div class="col-12">
                    <input class="form-control" maxlength="120" placeholder="Ubicación / nota (ej: depósito, heladera 2)" data-campo="nota">
                </div>
            </div>
        </div>
    </template>
</form>

<?php if ($cargados !== []): ?>
    <h2 class="section-title">Cargados en esta visita</h2>
    <div class="card-soft">
        <?php foreach ($cargados as $v): ?>
            <?php $estado = VencimientoService::estado($v['fecha_vencimiento']); ?>
            <div class="venc-row">
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= e(Producto::nombreCompleto($v)) ?></div>
                    <div class="small"><?= fecha($v['fecha_vencimiento']) ?> <span class="venc-badge <?= $estado['clase'] ?>"><?= e($estado['etiqueta']) ?></span></div>
                    <?php if ($v['nota']): ?><div class="small text-body-secondary"><i class="bi bi-geo-alt"></i> <?= e($v['nota']) ?></div><?php endif; ?>
                </div>
                <div class="venc-cant"><?= $v['cantidad'] !== null ? (int) $v['cantidad'] : '—' ?> <small>u.</small></div>
                <form method="post" action="<?= url("/visitas/{$vid}/vencimientos/{$v['id']}/eliminar") ?>" data-confirm="¿Eliminar este vencimiento?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-icon" aria-label="Eliminar vencimiento"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
