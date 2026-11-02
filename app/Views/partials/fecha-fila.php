<?php
/** Una fecha anotada con sus botones: ya no está (✓) y borrar. */
use App\Core\Auth;
use App\Models\Fecha;

$estado = Fecha::estado($f['fecha_vencimiento']);
$nombre = Fecha::nombre($f);
$detalle = array_filter([
    ($conLocal ?? true) ? $f['local'] : null,
    $f['cantidad'] !== null ? (int) $f['cantidad'] . ' u.' : null,
    $f['nota'],
    is_admin() && (int) $f['user_id'] !== Auth::id() ? $f['usuario'] : null,
]);
?>
<div class="fecha-fila<?= (int) $f['id'] === (int) ($nueva ?? 0) ? ' is-nueva' : '' ?>" data-fecha-fila>
    <div class="corta-fecha <?= $estado['clase'] ?>"><?= fecha($f['fecha_vencimiento'], 'd/m') ?><small><?= fecha($f['fecha_vencimiento'], 'Y') ?></small></div>
    <div class="min-w-0 flex-grow-1">
        <div class="fw-semibold text-truncate"><?= e($nombre) ?></div>
        <?php if ($detalle): ?><div class="small text-body-secondary text-truncate"><?= e(implode(' · ', $detalle)) ?></div><?php endif; ?>
        <span class="venc-badge <?= $estado['clase'] ?>"><?= e($estado['etiqueta']) ?></span>
    </div>
    <div class="fecha-acciones">
        <button type="button" class="btn btn-sm btn-outline-success" data-fecha-accion="retirar"
                data-url="<?= url('/fechas/' . (int) $f['id'] . '/retirar') ?>" title="Ya no está (vendido o retirado)" aria-label="Ya no está: <?= e($nombre) ?>">
            <i class="bi bi-check2"></i>
        </button>
        <button type="button" class="btn-icon" data-fecha-accion="borrar"
                data-url="<?= url('/fechas/' . (int) $f['id'] . '/eliminar') ?>" aria-label="Borrar <?= e($nombre) ?>">
            <i class="bi bi-trash3"></i>
        </button>
    </div>
</div>
