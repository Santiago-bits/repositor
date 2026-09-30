<?php
use App\Models\Configuracion;

$marcados = array_map('intval', (array) old('locales', []));
?>
<a class="back-link" href="<?= url('/visitas/' . $visita['id']) ?>"><i class="bi bi-arrow-left"></i> Volver a la visita</a>

<div class="page-head">
    <div class="min-w-0">
        <h1>Registrar promoción</h1>
        <div class="small text-body-secondary text-truncate"><i class="bi bi-geo-alt"></i> <?= e($visita['local']) ?></div>
    </div>
</div>

<form method="post" action="<?= url('/visitas/' . $visita['id'] . '/promociones') ?>" novalidate>
    <?= csrf_field() ?>
    <?= partial('promo-campos', ['promo' => $promo, 'grupos' => $grupos, 'atajos' => $atajos]) ?>

    <?php if ($otros !== []): ?>
        <div class="mb-3">
            <span class="form-label d-block">¿También está en otros locales?</span>
            <div class="card-soft check-list">
                <?php foreach ($otros as $l): ?>
                    <label class="form-check check-row">
                        <input class="form-check-input" type="checkbox" name="locales[]" value="<?= $l['id'] ?>" <?= in_array((int) $l['id'], $marcados, true) ? 'checked' : '' ?>>
                        <span class="form-check-label"><?= e($l['nombre']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <p class="small text-body-secondary">
        <i class="bi bi-info-circle"></i> El día que termina (y hasta <?= (int) Configuracion::get('conteo_promos_dias_gracia', '2') ?> días después) aparece en <strong>Conteo de promociones</strong>.
    </p>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-megaphone me-1"></i> Guardar promoción</button>
    </div>
</form>
