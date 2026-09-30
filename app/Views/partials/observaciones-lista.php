<?php
/** Recibe $observaciones, $editable, $mostrarProducto y $productoId (null = toda la visita). */
$extra = $productoId ? 'scope=producto&producto_id=' . (int) $productoId : 'scope=visita';
?>
<?php if ($observaciones === []): ?>
    <p class="text-body-secondary small mb-0">Sin observaciones.</p>
<?php else: ?>
    <?php foreach ($observaciones as $o): ?>
        <div class="obs-row">
            <i class="bi bi-chat-left-text text-primary"></i>
            <div class="flex-grow-1 min-w-0">
                <div><?= e($o['texto']) ?></div>
                <div class="small text-body-secondary">
                    <?= fecha($o['created_at'], 'H:i') ?>
                    <?php if ($mostrarProducto && $o['producto']): ?> · <?= e(trim($o['producto'] . ' ' . $o['presentacion'])) ?><?php endif; ?>
                </div>
            </div>
            <?php if ($editable): ?>
                <button type="button" class="btn-icon" aria-label="Eliminar observación"
                        data-accion="<?= url('/observaciones/' . $o['id'] . '/eliminar') ?>" data-extra="<?= e($extra) ?>"
                        data-destino="#observaciones-lista" data-confirm="¿Eliminar esta observación?">
                    <i class="bi bi-trash3"></i>
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
