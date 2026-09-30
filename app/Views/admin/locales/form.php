<?php
use App\Models\Local;

$v = fn (string $campo, mixed $default = '') => old($campo, $local[$campo] ?? $default);
$check = fn (string $campo, bool $default) => has_old() ? (bool) old($campo, false) : (bool) ($local[$campo] ?? $default);
$tipoActual = $v('tipo', 'supermercado');
?>

<div class="page-head">
    <h1><?= e($title) ?></h1>
    <a class="btn btn-link" href="<?= url('/admin/locales') ?>">Cancelar</a>
</div>

<form method="post" action="<?= url($local ? "/admin/locales/{$local['id']}" : '/admin/locales') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control<?= invalid('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" maxlength="120" required autofocus>
        <?= field_error('nombre') ?>
    </div>

    <div class="mb-3">
        <span class="form-label d-block">Tipo de comercio</span>
        <div class="option-grid">
            <?php foreach (Local::TIPOS as $valor => $etiqueta): ?>
                <input type="radio" class="btn-check" name="tipo" id="tipo-<?= $valor ?>" value="<?= $valor ?>" <?= $tipoActual === $valor ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="tipo-<?= $valor ?>"><i class="bi <?= Local::ICONOS[$valor] ?>"></i> <?= $etiqueta ?></label>
            <?php endforeach; ?>
        </div>
        <?= field_error('tipo') ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="direccion">Dirección</label>
        <input class="form-control<?= invalid('direccion') ?>" id="direccion" name="direccion" value="<?= e($v('direccion')) ?>" maxlength="200">
        <?= field_error('direccion') ?>
    </div>

    <div class="geo-box mb-3">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <span class="fw-semibold"><i class="bi bi-geo-alt"></i> Ubicación del local</span>
            <a class="small" href="#" target="_blank" rel="noopener" data-map-link data-lat="latitud" data-lng="longitud" hidden>Ver en mapa <i class="bi bi-box-arrow-up-right"></i></a>
        </div>
        <button type="button" class="btn btn-primary w-100 mb-2" data-geo-fill data-lat="latitud" data-lng="longitud" data-status="geo-status">
            <i class="bi bi-crosshair"></i> Usar mi ubicación actual
        </button>
        <div id="geo-status" class="small mb-2" role="status" aria-live="polite"></div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label small" for="latitud">Latitud</label>
                <input class="form-control<?= invalid('latitud') ?>" id="latitud" name="latitud" value="<?= e($v('latitud')) ?>" inputmode="decimal" placeholder="-31.39…">
                <?= field_error('latitud') ?>
            </div>
            <div class="col-6">
                <label class="form-label small" for="longitud">Longitud</label>
                <input class="form-control<?= invalid('longitud') ?>" id="longitud" name="longitud" value="<?= e($v('longitud')) ?>" inputmode="decimal" placeholder="-58.02…">
                <?= field_error('longitud') ?>
            </div>
        </div>
        <p class="form-text mb-0">Tip: en Google Maps mantené apretado el punto, copiá las coordenadas y pegalas en «Latitud».</p>
    </div>

    <div class="mb-3">
        <label class="form-label" for="radio_m">Radio de detección</label>
        <div class="input-group">
            <input class="form-control<?= invalid('radio_m') ?>" type="number" id="radio_m" name="radio_m" value="<?= e($v('radio_m', 100)) ?>" min="10" max="2000" step="10" inputmode="numeric" required>
            <span class="input-group-text">metros</span>
        </div>
        <?= field_error('radio_m') ?>
        <div class="form-text">Recomendado: 100 m. Subilo si adentro del local el GPS no lo detecta.</div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="telefono">Teléfono <span class="text-body-secondary">(opcional)</span></label>
            <input class="form-control<?= invalid('telefono') ?>" type="tel" id="telefono" name="telefono" value="<?= e($v('telefono')) ?>" maxlength="40" inputmode="tel">
            <?= field_error('telefono') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="contacto">Contacto <span class="text-body-secondary">(opcional)</span></label>
            <input class="form-control<?= invalid('contacto') ?>" id="contacto" name="contacto" value="<?= e($v('contacto')) ?>" maxlength="120" placeholder="Ej: Marta (encargada)">
            <?= field_error('contacto') ?>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="observaciones">Observaciones</label>
        <textarea class="form-control<?= invalid('observaciones') ?>" id="observaciones" name="observaciones" maxlength="1000" rows="3"><?= e($v('observaciones')) ?></textarea>
        <?= field_error('observaciones') ?>
    </div>

    <div class="card-soft px-3 py-2 mb-3">
        <div class="form-check form-switch py-2">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $check('activo', true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Local activo</label>
        </div>
        <div class="form-check form-switch py-2">
            <input class="form-check-input" type="checkbox" role="switch" id="es_prueba" name="es_prueba" value="1" <?= $check('es_prueba', false) ? 'checked' : '' ?>>
            <label class="form-check-label" for="es_prueba">Datos de prueba (coordenadas ficticias)</label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar</button>
    </div>
</form>

<?php if ($local): ?>
    <form method="post" action="<?= url("/admin/locales/{$local['id']}/estado") ?>" class="mt-3"
          data-confirm="<?= $local['activo'] ? '¿Desactivar este local? Deja de aparecer para los repositores, pero se conserva su historial.' : '¿Volver a activar este local?' ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-<?= $local['activo'] ? 'danger' : 'success' ?> w-100" type="submit">
            <i class="bi bi-<?= $local['activo'] ? 'slash-circle' : 'check-circle' ?> me-1"></i>
            <?= $local['activo'] ? 'Desactivar local' : 'Activar local' ?>
        </button>
    </form>
<?php endif; ?>
