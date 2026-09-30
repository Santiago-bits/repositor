<?php
/** Campos comunes del producto. Recibe $producto (array|null), $categorias y opcional $completo. */
use App\Models\Producto;

$v = fn (string $campo) => old($campo, $producto[$campo] ?? '');
$categoriaActual = (int) $v('categoria_id');
$unidadActual = $v('unidad_medida');
?>
<div class="mb-3">
    <label class="form-label" for="nombre">Nombre</label>
    <input class="form-control<?= invalid('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" maxlength="150" required placeholder="Ej: Mirin" <?= $v('nombre') === '' ? 'autofocus' : '' ?>>
    <?= field_error('nombre') ?>
</div>

<div class="row g-2 mb-3">
    <div class="col-7">
        <label class="form-label" for="marca">Marca</label>
        <input class="form-control<?= invalid('marca') ?>" id="marca" name="marca" value="<?= e($v('marca')) ?>" maxlength="80">
        <?= field_error('marca') ?>
    </div>
    <div class="col-5">
        <label class="form-label" for="presentacion">Presentación</label>
        <input class="form-control<?= invalid('presentacion') ?>" id="presentacion" name="presentacion" value="<?= e($v('presentacion')) ?>" maxlength="40" placeholder="500 ml">
        <?= field_error('presentacion') ?>
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="codigo_barras">Código de barras</label>
    <div class="input-group">
        <input class="form-control font-monospace<?= invalid('codigo_barras') ?>" id="codigo_barras" name="codigo_barras" value="<?= e($v('codigo_barras')) ?>" maxlength="32" inputmode="numeric" autocomplete="off">
        <button class="btn btn-outline-primary" type="button" data-escanear-a="codigo_barras" aria-label="Escanear código">
            <i class="bi bi-upc-scan"></i> Escanear
        </button>
    </div>
    <?= field_error('codigo_barras') ?>
</div>

<div class="row g-2 mb-3">
    <div class="col-7">
        <label class="form-label" for="categoria_id">Categoría</label>
        <select class="form-select<?= invalid('categoria_id') ?>" id="categoria_id" name="categoria_id">
            <option value="">Sin categoría</option>
            <?php foreach ($categorias as $id => $etiqueta): ?>
                <option value="<?= $id ?>" <?= $categoriaActual === $id ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
            <?php endforeach; ?>
        </select>
        <?= field_error('categoria_id') ?>
    </div>
    <div class="col-5">
        <label class="form-label" for="unidad_medida">Unidad</label>
        <select class="form-select<?= invalid('unidad_medida') ?>" id="unidad_medida" name="unidad_medida">
            <option value="">—</option>
            <?php foreach (Producto::UNIDADES as $valor => $etiqueta): ?>
                <option value="<?= $valor ?>" <?= $unidadActual === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
            <?php endforeach; ?>
        </select>
        <?= field_error('unidad_medida') ?>
    </div>
</div>

<?php if (!empty($completo)): ?>
    <div class="mb-3">
        <label class="form-label" for="descripcion">Descripción <span class="text-body-secondary">(opcional)</span></label>
        <textarea class="form-control<?= invalid('descripcion') ?>" id="descripcion" name="descripcion" maxlength="1000" rows="2"><?= e($v('descripcion')) ?></textarea>
        <?= field_error('descripcion') ?>
    </div>
<?php endif; ?>
