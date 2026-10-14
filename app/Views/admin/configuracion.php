<div class="page-head">
    <h1>Configuración</h1>
</div>

<form method="post" action="<?= url('/admin/configuracion') ?>" novalidate>
    <?= csrf_field() ?>

    <h2 class="section-title mt-0">Vencimientos</h2>
    <div class="card-soft p-3 mb-3">
        <div class="venc-leyenda mb-3">
            <span class="venc-badge venc-vencido">Vencido</span>
            <span class="venc-badge venc-hoy">Vence hoy</span>
            <span class="venc-badge venc-pocos">Pocos días</span>
            <span class="venc-badge venc-proximo">Próximamente</span>
            <span class="venc-badge venc-ok">Sin riesgo</span>
        </div>
        <?php foreach (['vencimiento_dias_critico', 'vencimiento_dias_proximo'] as $clave): ?>
            <?php [$etiqueta, $min, $max, $ayuda] = $campos[$clave]; ?>
            <div class="mb-3">
                <label class="form-label" for="<?= $clave ?>"><?= e($etiqueta) ?></label>
                <div class="input-group">
                    <input class="form-control<?= invalid($clave) ?>" type="number" id="<?= $clave ?>" name="<?= $clave ?>"
                           min="<?= $min ?>" max="<?= $max ?>" value="<?= e(old($clave, $valores[$clave])) ?>" inputmode="numeric">
                    <span class="input-group-text">días</span>
                </div>
                <?= field_error($clave) ?>
                <div class="form-text"><?= e($ayuda) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title">Promociones</h2>
    <div class="card-soft p-3 mb-3">
        <?php [$etiqueta, $min, $max, $ayuda] = $campos['conteo_promos_dias_gracia']; ?>
        <label class="form-label" for="conteo_promos_dias_gracia"><?= e($etiqueta) ?></label>
        <div class="input-group">
            <input class="form-control<?= invalid('conteo_promos_dias_gracia') ?>" type="number" id="conteo_promos_dias_gracia" name="conteo_promos_dias_gracia"
                   min="<?= $min ?>" max="<?= $max ?>" value="<?= e(old('conteo_promos_dias_gracia', $valores['conteo_promos_dias_gracia'])) ?>" inputmode="numeric">
            <span class="input-group-text">días</span>
        </div>
        <?= field_error('conteo_promos_dias_gracia') ?>
        <div class="form-text"><?= e($ayuda) ?></div>
    </div>

    <h2 class="section-title">Entrar con Google</h2>
    <div class="card-soft p-3 mb-3">
        <?php $googleOk = $valores['google_client_id'] !== ''; ?>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="fw-semibold">ID de cliente de Google</span>
            <?php if ($googleOk): ?>
                <span class="badge text-bg-success"><i class="bi bi-check-lg"></i> Configurado</span>
            <?php else: ?>
                <span class="badge text-bg-secondary">Sin configurar</span>
            <?php endif; ?>
        </div>
        <label class="visually-hidden" for="google_client_id">ID de cliente de Google</label>
        <input class="form-control font-monospace small<?= invalid('google_client_id') ?>" type="password" id="google_client_id" name="google_client_id"
               value="" placeholder="<?= $googleOk ? 'Pegá uno nuevo solo si querés cambiarlo' : 'Pegá el ID (termina en .apps.googleusercontent.com)' ?>"
               autocomplete="off" autocapitalize="off" spellcheck="false">
        <?= field_error('google_client_id') ?>
        <?php if ($googleOk): ?>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="google_quitar" name="google_quitar" value="1">
                <label class="form-check-label small" for="google_quitar">Quitar (sacar el botón de Google del login)</label>
            </div>
        <?php endif; ?>
        <div class="form-text">Con esto aparece el botón «Iniciar sesión con Google». Cada usuario vincula su Gmail en Perfil.</div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar</button>
    </div>
</form>
