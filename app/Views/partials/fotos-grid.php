<?php
/** Recibe $fotos, $editable, $mostrarProducto y $productoId (null = fotos de toda la visita). */
$extra = $productoId ? 'scope=producto&producto_id=' . (int) $productoId : 'scope=visita';
?>
<?php if ($fotos === []): ?>
    <p class="text-body-secondary small mb-0">Todavía no hay fotos.</p>
<?php else: ?>
    <div class="foto-grid">
        <?php foreach ($fotos as $f): ?>
            <figure class="foto-item">
                <a href="<?= url('/fotos/' . $f['id']) ?>" target="_blank" rel="noopener">
                    <img src="<?= url('/fotos/' . $f['id']) ?>" alt="Foto<?= $f['producto'] ? ' de ' . e($f['producto']) : '' ?>" loading="lazy">
                </a>
                <?php
                // Qué muestra la foto: tipo (Heladera…) y/o descripción; si es de un producto, el producto.
                $partes = array_filter([
                    isset(App\Models\Foto::TIPOS[$f['tipo'] ?? '']) ? App\Models\Foto::TIPOS[$f['tipo']][0] : null,
                    $f['descripcion'] ?? null,
                    $mostrarProducto && $f['producto'] ? trim($f['producto'] . ' ' . $f['presentacion']) : null,
                ]);
                ?>
                <?php if ($partes || $mostrarProducto): ?>
                    <figcaption><?= $partes ? e(implode(' · ', $partes)) : 'Foto' ?></figcaption>
                <?php endif; ?>
                <?php if ($editable): ?>
                    <button type="button" class="foto-borrar" aria-label="Eliminar foto"
                            data-accion="<?= url('/fotos/' . $f['id'] . '/eliminar') ?>" data-extra="<?= e($extra) ?>"
                            data-destino="#fotos-lista" data-confirm="¿Eliminar esta foto?">
                        <i class="bi bi-x-lg"></i>
                    </button>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
