<div class="page-head">
    <h1>Categorías</h1>
</div>

<form class="card-soft p-3 mb-4" method="post" action="<?= url('/admin/categorias') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
            <label class="form-label" for="nombre">Nueva categoría</label>
            <input class="form-control<?= invalid('nombre') ?>" id="nombre" name="nombre" value="<?= e(old('nombre')) ?>" maxlength="80" placeholder="Ej: Salsas" required>
            <?= field_error('nombre') ?>
        </div>
        <div class="col-8 col-md-4">
            <label class="form-label" for="parent_id">Dentro de</label>
            <select class="form-select<?= invalid('parent_id') ?>" id="parent_id" name="parent_id">
                <option value="">— Es principal —</option>
                <?php foreach ($principales as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int) old('parent_id') === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error('parent_id') ?>
        </div>
        <div class="col-4 col-md-3">
            <button class="btn btn-primary w-100" type="submit" style="min-height:48px"><i class="bi bi-plus-lg"></i> Agregar</button>
        </div>
    </div>
</form>

<?php if ($arbol === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-tags"></i>
        <p class="mb-0">Todavía no hay categorías.</p>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($arbol as $c): ?>
            <div class="card-soft categoria-grupo">
                <a class="categoria-fila<?= $c['activo'] ? '' : ' is-inactive' ?>" href="<?= url("/admin/categorias/{$c['id']}/editar") ?>">
                    <i class="bi bi-tag-fill text-primary"></i>
                    <span class="fw-semibold flex-grow-1"><?= e($c['nombre']) ?></span>
                    <span class="small text-body-secondary"><?= (int) $c['productos'] ?> prod.</span>
                    <?php if (!$c['activo']): ?><span class="badge text-bg-secondary">Inactiva</span><?php endif; ?>
                    <i class="bi bi-chevron-right text-body-secondary"></i>
                </a>
                <?php foreach ($c['hijas'] as $h): ?>
                    <a class="categoria-fila categoria-hija<?= $h['activo'] ? '' : ' is-inactive' ?>" href="<?= url("/admin/categorias/{$h['id']}/editar") ?>">
                        <i class="bi bi-arrow-return-right text-body-secondary"></i>
                        <span class="flex-grow-1"><?= e($h['nombre']) ?></span>
                        <span class="small text-body-secondary"><?= (int) $h['productos'] ?> prod.</span>
                        <?php if (!$h['activo']): ?><span class="badge text-bg-secondary">Inactiva</span><?php endif; ?>
                        <i class="bi bi-chevron-right text-body-secondary"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
