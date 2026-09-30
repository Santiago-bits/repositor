<?php
use App\Models\Local;

$activo = has_old() ? (bool) old('activo', false) : (bool) ($producto['activo'] ?? true);

// Locales: lo que se reenvió tras un error, lo guardado, o todos los activos si es nuevo.
$oldLocales = old('locales', null);
$filaLocal = function (array $local) use ($oldLocales, $asignados): array {
    $id = (int) $local['id'];
    if (is_array($oldLocales)) {
        $f = $oldLocales[$id] ?? [];
        return [!empty($f['activo']), $f['stock_habitual'] ?? '', $f['ubicacion_gondola'] ?? ''];
    }
    if ($asignados === null) {
        return [(bool) $local['activo'], '', ''];
    }
    $f = $asignados[$id] ?? null;
    return [$f !== null, $f['stock_habitual'] ?? '', $f['ubicacion_gondola'] ?? ''];
};
?>

<div class="page-head">
    <h1><?= e($title) ?></h1>
    <a class="btn btn-link" href="<?= url('/admin/productos') ?>">Cancelar</a>
</div>

<form method="post" action="<?= url($producto ? "/admin/productos/{$producto['id']}" : '/admin/productos') ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <?= partial('producto-campos', ['producto' => $producto, 'categorias' => $categorias, 'completo' => true]) ?>

    <div class="mb-3">
        <span class="form-label d-block">Imagen principal</span>
        <div class="imagen-campo">
            <?php if (!empty($producto['imagen_path'])): ?>
                <img class="item-thumb item-thumb-lg" src="<?= url('/productos/' . $producto['id'] . '/imagen') ?>" alt="">
            <?php endif; ?>
            <div class="flex-grow-1">
                <input class="form-control<?= invalid('imagen') ?>" type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
                <?= field_error('imagen') ?>
                <?php if (!empty($producto['imagen_path'])): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="quitar_imagen" name="quitar_imagen" value="1">
                        <label class="form-check-label" for="quitar_imagen">Quitar imagen</label>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-text">Se achica automáticamente para no ocupar espacio.</div>
    </div>

    <div class="mb-3">
        <span class="form-label d-block">Locales donde se trabaja</span>
        <?php if ($locales === []): ?>
            <p class="text-body-secondary small">No hay locales cargados.</p>
        <?php else: ?>
            <div class="card-soft">
                <?php foreach ($locales as $l): ?>
                    <?php [$marcado, $stock, $ubicacion] = $filaLocal($l); $id = (int) $l['id']; ?>
                    <div class="local-row" data-local-row>
                        <label class="form-check m-0 d-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox" name="locales[<?= $id ?>][activo]" value="1" <?= $marcado ? 'checked' : '' ?> data-local-check>
                            <span class="form-check-label">
                                <i class="bi <?= Local::ICONOS[$l['tipo']] ?> text-body-secondary"></i> <?= e($l['nombre']) ?>
                                <?php if (!$l['activo']): ?><span class="badge text-bg-secondary">Inactivo</span><?php endif; ?>
                            </span>
                        </label>
                        <div class="row g-2 mt-1 local-row-extra">
                            <div class="col-5">
                                <input class="form-control form-control-sm" type="number" min="0" max="65535" inputmode="numeric"
                                       name="locales[<?= $id ?>][stock_habitual]" value="<?= e($stock) ?>" placeholder="Stock habitual" aria-label="Stock habitual en <?= e($l['nombre']) ?>">
                            </div>
                            <div class="col-7">
                                <input class="form-control form-control-sm" maxlength="80"
                                       name="locales[<?= $id ?>][ubicacion_gondola]" value="<?= e($ubicacion) ?>" placeholder="Góndola / ubicación" aria-label="Ubicación en <?= e($l['nombre']) ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-soft px-3 py-2 mb-3">
        <div class="form-check form-switch py-2">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $activo ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Producto activo</label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar</button>
    </div>
</form>

<?php if ($producto): ?>
    <form method="post" action="<?= url("/admin/productos/{$producto['id']}/estado") ?>" class="mt-3"
          data-confirm="<?= $producto['activo'] ? '¿Desactivar este producto? Deja de aparecer en búsquedas, pero se conserva su historial.' : '¿Volver a activar este producto?' ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-<?= $producto['activo'] ? 'danger' : 'success' ?> w-100" type="submit">
            <i class="bi bi-<?= $producto['activo'] ? 'slash-circle' : 'check-circle' ?> me-1"></i>
            <?= $producto['activo'] ? 'Desactivar producto' : 'Activar producto' ?>
        </button>
    </form>

    <form method="post" action="<?= url("/admin/productos/{$producto['id']}/eliminar") ?>" class="mt-2 text-center"
          data-confirm="¿Borrar este producto? Desaparece de la app; lo registrado en visitas anteriores se conserva.">
        <?= csrf_field() ?>
        <button class="btn btn-link text-danger" type="submit"><i class="bi bi-trash3"></i> Borrar producto</button>
    </form>
<?php endif; ?>
