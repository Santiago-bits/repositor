<?php use App\Models\Local; ?>
<div class="page-head">
    <h1>Tareas de hoy</h1>
    <span class="text-body-secondary small"><?= e(dia_semana()) ?> <?= date('d/m') ?></span>
</div>

<?php if ($locales === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-list-check"></i>
        <p class="mb-0">No tenés locales asignados.</p>
    </div>
<?php endif; ?>

<div class="item-list">
    <?php foreach ($locales as $l): ?>
        <section class="card-soft p-3">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="item-icon item-icon-sm"><i class="bi <?= Local::ICONOS[$l['tipo']] ?>"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-bold text-truncate"><?= e($l['nombre']) ?></div>
                    <div class="small text-body-secondary">
                        <?= $l['pendientes'] ? $l['pendientes'] . ' pendiente' . ($l['pendientes'] > 1 ? 's' : '') : 'Todo al día' ?>
                    </div>
                </div>
                <?php if ($abierta && (int) $abierta['local_id'] === (int) $l['id'] && $abierta['fecha'] === date('Y-m-d')): ?>
                    <a class="btn btn-primary btn-sm" href="<?= url('/visitas/' . $abierta['id']) ?>">Continuar</a>
                <?php else: ?>
                    <?= partial('form-ingresar', ['localId' => (int) $l['id'], 'abierta' => $abierta, 'clase' => 'btn btn-outline-primary btn-sm', 'texto' => 'Ingresar']) ?>
                <?php endif; ?>
            </div>

            <?php if ($l['conteo'] > 0): ?>
                <div class="tarea-mini text-danger fw-semibold"><i class="bi bi-megaphone-fill"></i> <?= $l['conteo'] ?> promo<?= $l['conteo'] > 1 ? 's' : '' ?> para contar</div>
            <?php endif; ?>
            <?php foreach ($l['tareas'] as $t): ?>
                <div class="tarea-mini<?= $t['hecha'] ? ' is-hecha' : '' ?>">
                    <?= $t['hecha'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<span class="prio-dot prio-' . e($t['prioridad']) . '"></span>' ?>
                    <span><?= e($t['nombre']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if ($l['tareas'] === [] && !$l['conteo']): ?>
                <p class="small text-body-secondary mb-0">Sin tareas para hoy.</p>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
