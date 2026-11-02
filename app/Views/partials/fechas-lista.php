<?php /** Lista de fechas. Recibe $fechas, $conLocal (mostrar el local) y opcional $nueva (id recién anotado, se resalta). */ ?>
<?php if ($fechas === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-calendar-check"></i>
        <p class="mb-0">Todavía no anotaste fechas acá.</p>
    </div>
<?php else: ?>
    <div class="card-soft fechas-lista">
        <?php foreach ($fechas as $f): ?>
            <?= partial('fecha-fila', ['f' => $f, 'conLocal' => $conLocal ?? true, 'nueva' => $nueva ?? 0]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
