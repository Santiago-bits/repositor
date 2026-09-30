<?php
/** Campos de una promoción. Recibe $promo, $grupos (etiqueta => [id => nombre]) y $atajos (texto => fecha). */
$v = fn (string $campo) => old($campo, $promo[$campo] ?? '');
$productoActual = (int) $v('producto_id');
?>
<div class="mb-3">
    <label class="form-label" for="producto_id">Producto</label>
    <div class="input-group">
        <select class="form-select<?= invalid('producto_id') ?>" id="producto_id" name="producto_id" required>
            <option value="">Elegí el producto…</option>
            <?php foreach ($grupos as $etiqueta => $opciones): ?>
                <?php if (count($grupos) > 1): ?><optgroup label="<?= e($etiqueta) ?>"><?php endif; ?>
                <?php foreach ($opciones as $id => $nombre): ?>
                    <option value="<?= $id ?>" <?= $productoActual === $id ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
                <?php if (count($grupos) > 1): ?></optgroup><?php endif; ?>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary" type="button" data-escanear-select="producto_id"
                data-codigo-endpoint="<?= url('/productos/codigo') ?>" aria-label="Escanear código del producto">
            <i class="bi bi-upc-scan"></i>
        </button>
    </div>
    <?= field_error('producto_id') ?>
</div>

<div class="row g-2 mb-2">
    <div class="col-6">
        <label class="form-label" for="fecha_inicio">Desde</label>
        <input class="form-control<?= invalid('fecha_inicio') ?>" type="date" id="fecha_inicio" name="fecha_inicio" value="<?= e($v('fecha_inicio')) ?>" required>
        <?= field_error('fecha_inicio') ?>
    </div>
    <div class="col-6">
        <label class="form-label" for="fecha_fin">Hasta</label>
        <input class="form-control<?= invalid('fecha_fin') ?>" type="date" id="fecha_fin" name="fecha_fin" value="<?= e($v('fecha_fin')) ?>" required>
        <?= field_error('fecha_fin') ?>
    </div>
</div>
<div class="chips mb-3">
    <?php foreach ($atajos as $texto => $fecha): ?>
        <button type="button" class="chip" data-set-valor="<?= e($fecha) ?>" data-target="fecha_fin"><?= e($texto) ?></button>
    <?php endforeach; ?>
</div>

<div class="row g-2 mb-3">
    <div class="col-6">
        <label class="form-label" for="precio_normal">Precio normal <span class="text-body-secondary">(opc.)</span></label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input class="form-control<?= invalid('precio_normal') ?>" id="precio_normal" name="precio_normal" value="<?= e($v('precio_normal')) ?>" inputmode="decimal">
        </div>
        <?= field_error('precio_normal') ?>
    </div>
    <div class="col-6">
        <label class="form-label" for="precio_promo">Precio promo <span class="text-body-secondary">(opc.)</span></label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input class="form-control<?= invalid('precio_promo') ?>" id="precio_promo" name="precio_promo" value="<?= e($v('precio_promo')) ?>" inputmode="decimal">
        </div>
        <?= field_error('precio_promo') ?>
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="observaciones">Observaciones <span class="text-body-secondary">(opc.)</span></label>
    <input class="form-control<?= invalid('observaciones') ?>" id="observaciones" name="observaciones" value="<?= e($v('observaciones')) ?>" maxlength="255" placeholder="Ej: 2x1, cartel en punta de góndola">
    <?= field_error('observaciones') ?>
</div>
