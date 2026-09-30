<?php
use App\Models\Producto;
use App\Models\RelevamientoProducto;

$query = http_build_query(array_filter($filtros));
?>
<div class="page-head">
    <h1>Reportes</h1>
</div>

<form class="card-soft p-3 mb-3" method="get" action="<?= url('/reportes') ?>">
    <div class="row g-2">
        <div class="col-6 col-md-3">
            <label class="form-label small" for="desde">Desde</label>
            <input class="form-control" type="date" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="hasta">Hasta</label>
            <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
        </div>
        <div class="col-<?= $usuarios ? '6' : '12' ?> col-md-3">
            <label class="form-label small" for="local_id">Local</label>
            <select class="form-select" id="local_id" name="local_id">
                <option value="">Todos</option>
                <?php foreach ($locales as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= (int) $filtros['local_id'] === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($usuarios): ?>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="user_id">Usuario</label>
                <select class="form-select" id="user_id" name="user_id">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int) $filtros['user_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['nombre'] . ' ' . $u['apellido']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-12">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i> Ver reporte</button>
        </div>
    </div>
</form>

<div class="row g-2 mb-3">
    <?php foreach ([
        ['Visitas', $resumen['visitas'], 'bi-clipboard-check', ''],
        ['Productos', $resumen['productos'], 'bi-box-seam', ''],
        ['Sin stock', $resumen['sin_stock'], 'bi-x-octagon', 'danger'],
        ['Bajo stock', $resumen['bajo'], 'bi-exclamation-triangle', 'warn'],
        ['Conteos promo', $resumen['promos'], 'bi-megaphone', ''],
        ['Locales', $resumen['locales'], 'bi-shop', ''],
    ] as [$label, $valor, $icono, $tono]): ?>
        <div class="col-4 col-md-2">
            <div class="stat-card stat-mini<?= $tono ? ' tone-' . $tono : '' ?>">
                <div class="stat-value"><?= (int) $valor ?></div>
                <div class="stat-label"><i class="bi <?= $icono ?>"></i> <?= $label ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<a class="btn btn-success btn-xl w-100 mb-3" href="<?= url('/reportes/csv?' . $query) ?>">
    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Descargar CSV (Excel)
</a>

<?php if ($filas === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-file-earmark-bar-graph"></i>
        <p class="mb-0">No hay registros en ese período.</p>
    </div>
<?php else: ?>
    <div class="card-soft table-responsive">
        <table class="table table-sm align-middle mb-0 reporte-tabla">
            <thead>
                <tr><th>Fecha</th><th>Local</th><th>Producto</th><th class="text-end">Stock</th><th>Estado</th><th>Venc.</th></tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $r): ?>
                    <tr>
                        <td class="text-nowrap"><?= fecha($r['fecha'], 'd/m') ?></td>
                        <td><?= e($r['local']) ?></td>
                        <td><?= e(Producto::nombreCompleto(['nombre' => $r['producto'], 'presentacion' => $r['presentacion']])) ?><?= $r['promo_desde'] ? ' <i class="bi bi-megaphone text-danger" title="Conteo de promoción"></i>' : '' ?></td>
                        <td class="text-end fw-semibold"><?= $r['stock'] !== null ? (int) $r['stock'] : '—' ?></td>
                        <td><?= $r['estado_stock'] ? '<span class="badge ' . RelevamientoProducto::ESTADOS[$r['estado_stock']][1] . '">' . RelevamientoProducto::ESTADOS[$r['estado_stock']][0] . '</span>' : '' ?></td>
                        <td class="small"><?= e($r['vencimientos'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($filas) >= 200): ?><p class="small text-body-secondary mt-2">Se muestran 200 filas. El CSV tiene todas.</p><?php endif; ?>
<?php endif; ?>

<?php if ($generales !== []): ?>
    <h2 class="section-title">Observaciones generales</h2>
    <div class="card-soft px-3">
        <?php foreach ($generales as $o): ?>
            <div class="obs-row">
                <i class="bi bi-chat-left-text text-primary"></i>
                <div><div><?= e($o['texto']) ?></div><div class="small text-body-secondary"><?= fecha($o['fecha'], 'd/m') ?> · <?= e($o['local']) ?></div></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
