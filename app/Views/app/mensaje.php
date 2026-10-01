<?php
$base = $visita ? '/visitas/' . $visita['id'] . '/mensaje' : '/mensaje';
$params = fn (string $t) => $visita ? '?tipo=' . $t : '?fecha=' . $fecha . '&tipo=' . $t;
?>
<a class="back-link" href="<?= url($visita ? '/visitas/' . $visita['id'] : '/historial') ?>" data-volver><i class="bi bi-arrow-left"></i> Volver</a>

<div class="page-head">
    <div class="min-w-0">
        <h1><?= e($title) ?></h1>
        <div class="small text-body-secondary"><?= $visita ? e($visita['local']) . ' · ' : '' ?><?= e(dia_semana(strtotime($fecha))) ?> <?= fecha($fecha) ?></div>
    </div>
</div>

<?php if (!$visita): ?>
    <form method="get" action="<?= url('/mensaje') ?>" class="mb-2">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
        <input class="form-control" type="date" name="fecha" value="<?= e($fecha) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()" aria-label="Fecha">
    </form>
<?php endif; ?>

<nav class="nav nav-pills filtro-pills mb-3">
    <a class="nav-link<?= $tipo === 'completo' ? ' active' : '' ?>" href="<?= url($base . $params('completo')) ?>">Completo</a>
    <a class="nav-link<?= $tipo === 'promos' ? ' active' : '' ?>" href="<?= url($base . $params('promos')) ?>">Promociones</a>
    <a class="nav-link<?= $tipo === 'faltantes' ? ' active' : '' ?>" href="<?= url($base . $params('faltantes')) ?>">Faltantes (vendedor)</a>
</nav>

<textarea id="mensaje" class="form-control mensaje-texto mb-3" rows="14" readonly aria-label="Mensaje generado"><?= e($mensaje) ?></textarea>

<div class="d-grid gap-2">
    <button class="btn btn-primary btn-xl" type="button" data-copiar="#mensaje"><i class="bi bi-clipboard me-1"></i> COPIAR MENSAJE</button>
    <a class="btn btn-success btn-xl" href="https://wa.me/?text=<?= rawurlencode($mensaje) ?>" target="_blank" rel="noopener">
        <i class="bi bi-whatsapp me-1"></i> ENVIAR POR WHATSAPP
    </a>
</div>
