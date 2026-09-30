<?php
$v = fn (string $campo) => old($campo, $categoria[$campo] ?? '');
$activo = has_old() ? (bool) old('activo', false) : (bool) $categoria['activo'];
$padreActual = (int) $v('parent_id');
?>

<div class="page-head">
    <h1><?= e($title) ?></h1>
    <a class="btn btn-link" href="<?= url('/admin/categorias') ?>">Cancelar</a>
</div>

<form method="post" action="<?= url("/admin/categorias/{$categoria['id']}") ?>" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control<?= invalid('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" maxlength="80" required>
        <?= field_error('nombre') ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="parent_id">Dentro de</label>
        <?php if ($tieneHijas): ?>
            <input type="hidden" name="parent_id" value="">
            <p class="form-text mt-0">Es una categoría principal con subcategorías.</p>
        <?php else: ?>
            <select class="form-select<?= invalid('parent_id') ?>" id="parent_id" name="parent_id">
                <option value="">— Es principal —</option>
                <?php foreach ($principales as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $padreActual === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error('parent_id') ?>
        <?php endif; ?>
    </div>

    <div class="card-soft px-3 py-2 mb-3">
        <div class="form-check form-switch py-2">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $activo ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Categoría activa</label>
        </div>
        <div class="form-text pb-2">Si la desactivás, no aparece para elegir en productos nuevos. Los productos que ya la tienen la conservan.</div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar</button>
    </div>
</form>
