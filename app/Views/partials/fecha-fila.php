<?php
/** Una fecha anotada: ✓ ya no está, y el lápiz abre el editor (ahí también se borra). */
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
<div class="fecha-fila<?= (int) $f['id'] === (int) ($nueva ?? 0) ? ' is-nueva' : '' ?>" data-fecha-fila
     data-url="<?= url('/fechas/' . (int) $f['id']) ?>" data-buscar-url="<?= url('/fechas/productos') ?>" data-con-local="<?= ($conLocal ?? true) ? '1' : '0' ?>"
     data-nombre="<?= e($nombre) ?>" data-fecha="<?= fecha($f['fecha_vencimiento'], 'd/m/Y') ?>"
     data-cantidad="<?= e((string) $f['cantidad']) ?>" data-nota="<?= e((string) $f['nota']) ?>">
    <div class="corta-fecha <?= $estado['clase'] ?>"><?= fecha($f['fecha_vencimiento'], 'd/m') ?><small><?= fecha($f['fecha_vencimiento'], 'Y') ?></small></div>
    <div class="min-w-0 flex-grow-1 fecha-cuerpo" data-fecha-editar>
        <div class="fw-semibold fecha-texto"><?= e($nombre) ?></div>
        <?php if ($detalle): ?><div class="small text-body-secondary fecha-texto"><?= e(implode(' · ', $detalle)) ?></div><?php endif; ?>
        <span class="venc-badge <?= $estado['clase'] ?>"><?= e($estado['etiqueta']) ?></span>
    </div>
    <div class="fecha-acciones">
        <button type="button" class="btn btn-sm btn-outline-success" data-fecha-accion="retirar"
                data-url="<?= url('/fechas/' . (int) $f['id'] . '/retirar') ?>" title="Ya no está (vendido o retirado)" aria-label="Ya no está: <?= e($nombre) ?>">
            <i class="bi bi-check2"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-fecha-editar title="Editar" aria-label="Editar <?= e($nombre) ?>">
            <i class="bi bi-pencil"></i>
        </button>
    </div>
</div>
