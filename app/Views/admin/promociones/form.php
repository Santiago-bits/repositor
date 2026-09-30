<?php
use App\Models\Promocion;

$marcados = array_map('intval', (array) old('locales', $asignados));
$estadoActual = old('estado', $promo['estado'] ?? 'activa');
?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <a class="btn btn-link" href="<?= url('/admin/promociones') ?>">Cancelar</a>
</div>

<form method="post" action="<?= url($esNueva ? '/admin/promociones' : '/admin/promociones/' . $promo['id']) ?>" novalidate>
    <?= csrf_field() ?>
    <?= partial('promo-campos', ['promo' => $promo, 'grupos' => $grupos, 'atajos' => $atajos]) ?>

    <div class="mb-3">
        <span class="form-label d-block">Locales</span>
        <div class="card-soft check-list">
            <?php foreach ($locales as $l): ?>
                <label class="form-check check-row">
                    <input class="form-check-input" type="checkbox" name="locales[]" value="<?= $l['id'] ?>" <?= in_array((int) $l['id'], $marcados, true) ? 'checked' : '' ?>>
                    <span class="form-check-label"><?= e($l['nombre']) ?><?php if (!$l['activo']): ?> <span class="badge text-bg-secondary">Inactivo</span><?php endif; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= field_error('locales') ?>
    </div>

    <?php if (!$esNueva): ?>
        <div class="mb-3">
            <label class="form-label" for="estado">Estado</label>
            <select class="form-select" id="estado" name="estado">
                <?php foreach (Promocion::ESTADOS as $valor => $texto): ?>
                    <option value="<?= $valor ?>" <?= $estadoActual === $valor ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($promo['creador'])): ?><div class="form-text">Registrada por <?= e($promo['creador']) ?>.</div><?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar</button>
    </div>
</form>

<?php if (!$esNueva && $promo['estado'] === 'activa'): ?>
    <form method="post" action="<?= url('/admin/promociones/' . $promo['id'] . '/cancelar') ?>" class="mt-3" data-confirm="¿Cancelar esta promoción? Deja de aparecer en el conteo.">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-x-circle me-1"></i> Cancelar promoción</button>
    </form>
<?php endif; ?>
