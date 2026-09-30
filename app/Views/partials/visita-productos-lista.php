<?php
use App\Models\Producto;
use App\Models\RelevamientoProducto;

// Pendientes primero: es lo que falta hacer.
usort($productos, fn ($a, $b) => [$a['rp_id'] !== null] <=> [$b['rp_id'] !== null]);
$total = count($productos);
$hechos = count(array_filter($productos, fn ($p) => $p['rp_id'] !== null));
?>
<?php if ($productos === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-box-seam"></i>
        <p class="mb-0">Este local todavía no tiene productos asignados.<br>Buscalos o escanealos: al registrarlos quedan asociados al local.</p>
    </div>
<?php else: ?>
    <div class="progreso mb-3">
        <div class="d-flex justify-content-between small mb-1">
            <span class="text-body-secondary">Productos del local</span>
            <span><strong><?= $hechos ?></strong> de <?= $total ?> registrados</span>
        </div>
        <div class="progress" role="progressbar" aria-valuenow="<?= $hechos ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>" style="height: 8px">
            <div class="progress-bar" style="width: <?= $total ? round($hechos * 100 / $total) : 0 ?>%"></div>
        </div>
    </div>

    <div class="item-list">
        <?php foreach ($productos as $p): ?>
            <a class="item-card<?= $p['rp_id'] !== null ? ' is-registrado' : '' ?>" href="<?= url("/visitas/{$visita['id']}/productos/{$p['id']}") ?>">
                <?php if ($p['imagen_path']): ?>
                    <img class="item-thumb" src="<?= url('/productos/' . $p['id'] . '/imagen') ?>" alt="" loading="lazy">
                <?php else: ?>
                    <div class="item-icon"><i class="bi bi-box-seam"></i></div>
                <?php endif; ?>
                <div class="item-body">
                    <div class="item-title"><?= e(Producto::nombreCompleto($p)) ?></div>
                    <div class="item-sub"><?= e(implode(' · ', array_filter([$p['marca'], $p['ubicacion_gondola']]))) ?: '&nbsp;' ?></div>
                </div>
                <?php if ($p['rp_id'] !== null): ?>
                    <div class="text-end">
                        <div class="registro-stock"><?= $p['stock'] !== null ? (int) $p['stock'] : '—' ?></div>
                        <?php if ($p['estado_stock']): ?>
                            <span class="badge <?= RelevamientoProducto::ESTADOS[$p['estado_stock']][1] ?>"><?= RelevamientoProducto::ESTADOS[$p['estado_stock']][0] ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span class="badge badge-muted">Pendiente</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
