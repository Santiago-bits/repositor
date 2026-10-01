<?php
/**
 * Una fecha corta (lote por vencer) con el botón Retirar. Recibe $v (fila de Vencimiento::proximos)
 * y opcional $conLocal (mostrar el nombre del local; en la visita no hace falta).
 */
use App\Models\Producto;
use App\Services\VencimientoService;

$estado = VencimientoService::estado($v['fecha_vencimiento']);
$nombre = Producto::nombreCompleto($v);
$detalle = array_filter([
    ($conLocal ?? true) ? $v['local'] : null,
    $v['cantidad'] !== null ? (int) $v['cantidad'] . ' u.' : null,
    $v['nota'],
]);
?>
<div class="corta-row">
    <div class="corta-fecha <?= $estado['clase'] ?>"><?= fecha($v['fecha_vencimiento'], 'd/m') ?></div>
    <?= producto_thumb($v) ?>
    <a class="min-w-0 flex-grow-1 text-reset text-decoration-none" href="<?= url('/productos/' . (int) $v['producto_id']) ?>">
        <div class="fw-semibold text-truncate"><?= e($nombre) ?></div>
        <?php if ($detalle): ?><div class="small text-body-secondary text-truncate"><?= e(implode(' · ', $detalle)) ?></div><?php endif; ?>
        <span class="venc-badge <?= $estado['clase'] ?>"><?= e($estado['etiqueta']) ?></span>
    </a>
    <form method="post" action="<?= url('/vencimientos/' . (int) $v['id'] . '/retirar') ?>"
          data-confirm="<?= e("¿Ya retiraste {$nombre} de {$v['local']}?") ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-box-arrow-up"></i> Retirar</button>
    </form>
</div>
