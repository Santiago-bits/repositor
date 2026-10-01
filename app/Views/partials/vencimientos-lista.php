<?php use App\Services\VencimientoService; ?>
<?php if ($vencimientos === []): ?>
    <p class="text-body-secondary small mb-0 py-1">Sin vencimientos cargados.</p>
<?php else: ?>
    <?php foreach ($vencimientos as $v): ?>
        <?php $estado = VencimientoService::estado($v['fecha_vencimiento']); ?>
        <div class="venc-row">
            <div class="flex-grow-1">
                <div class="fw-semibold"><?= fecha($v['fecha_vencimiento']) ?></div>
                <span class="venc-badge <?= $estado['clase'] ?>"><?= e($estado['etiqueta']) ?></span>
                <?php if (!empty($v['nota'])): ?><div class="small text-body-secondary"><i class="bi bi-geo-alt"></i> <?= e($v['nota']) ?></div><?php endif; ?>
            </div>
            <div class="venc-cant"><?= $v['cantidad'] !== null ? (int) $v['cantidad'] : '—' ?> <small>u.</small></div>
            <?php if ($editable): ?>
                <button type="button" class="btn-icon" aria-label="Eliminar vencimiento"
                        data-accion="<?= url("/visitas/{$visitaId}/vencimientos/{$v['id']}/eliminar") ?>"
                        data-destino="#vencimientos" data-confirm="¿Eliminar este vencimiento?">
                    <i class="bi bi-trash3"></i>
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
