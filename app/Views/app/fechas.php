<div class="page-head">
    <h1>Fechas</h1>
    <p class="page-sub">
        <?= $total === 1 ? '1 fecha pendiente' : "{$total} fechas pendientes" ?><?= is_admin() ? ' de todo el equipo' : '' ?>.
        Tocá <i class="bi bi-check2"></i> cuando ya no esté.
    </p>
</div>

<?php if ($porLocal === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-calendar-check"></i>
        <p class="fw-semibold text-body mb-1">No tenés fechas pendientes.</p>
        <p class="small mb-3">Anotá las fechas cortas desde el local.</p>
        <a class="btn btn-primary btn-pill" href="<?= url('/') ?>"><i class="bi bi-plus-lg me-1"></i> Anotar</a>
    </div>
<?php endif; ?>

<?php foreach ($porLocal as $localId => $grupo): ?>
    <h2 class="section-title section-title-accion">
        <span><?= e($grupo['nombre']) ?> (<?= count($grupo['fechas']) ?>)</span>
        <a href="<?= url('/?local=' . (int) $localId) ?>"><i class="bi bi-plus-lg"></i> Anotar</a>
    </h2>
    <?= partial('fechas-lista', ['fechas' => $grupo['fechas'], 'conLocal' => false]) ?>
<?php endforeach; ?>
