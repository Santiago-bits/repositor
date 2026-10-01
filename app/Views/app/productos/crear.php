<div class="page-head">
    <h1>Nuevo producto</h1>
    <a class="btn btn-link" href="<?= url($visitaId ? "/visitas/{$visitaId}" : '/productos') ?>">Cancelar</a>
</div>

<?php if ($escaneado !== ''): ?>
    <div class="notice mb-3">
        <i class="bi bi-upc-scan"></i>
        <div>
            <strong>Producto no registrado</strong>
            <p class="mb-0">El código <span class="font-monospace"><?= e($escaneado) ?></span> todavía no existe. Completá los datos y queda guardado para siempre.</p>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/productos') ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($visitaId): ?><input type="hidden" name="visita" value="<?= (int) $visitaId ?>"><?php endif; ?>
    <?= partial('producto-campos', ['producto' => $producto, 'categorias' => $categorias]) ?>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar producto</button>
    </div>
</form>
