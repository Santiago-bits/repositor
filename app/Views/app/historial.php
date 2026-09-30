<?php
use App\Models\Local;
use App\Models\Relevamiento;
?>
<div class="page-head">
    <h1>Historial</h1>
</div>

<div class="d-grid gap-2 mb-3" style="grid-template-columns: 1fr 1fr">
    <a class="btn btn-primary" href="<?= url('/mensaje') ?>"><i class="bi bi-chat-square-text"></i> Mensaje de hoy</a>
    <a class="btn btn-outline-primary" href="<?= url('/reportes') ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Reportes</a>
</div>

<?php if ($porFecha === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-clock-history"></i>
        <p class="mb-0">No hay visitas en los últimos <?= $dias ?> días.</p>
    </div>
<?php endif; ?>

<?php foreach ($porFecha as $fecha => $visitas): ?>
    <h2 class="section-title d-flex justify-content-between align-items-center">
        <span><?= e(dia_semana(strtotime($fecha))) ?> <?= fecha($fecha) ?></span>
        <a class="small text-decoration-none text-lowercase" href="<?= url('/mensaje?fecha=' . $fecha) ?>"><i class="bi bi-chat-square-text"></i> mensaje</a>
    </h2>
    <div class="item-list">
        <?php foreach ($visitas as $v): ?>
            <a class="item-card" href="<?= url('/visitas/' . $v['id']) ?>">
                <div class="item-icon"><i class="bi <?= Local::ICONOS[$v['tipo']] ?>"></i></div>
                <div class="item-body">
                    <div class="item-title"><?= e($v['local']) ?></div>
                    <div class="item-sub"><?= e(Relevamiento::actividad($v)) ?> · <?= (int) $v['productos'] ?> producto<?= (int) $v['productos'] === 1 ? '' : 's' ?></div>
                    <div class="item-meta">
                        <?php if ($v['fotos'] > 0): ?><span><i class="bi bi-camera"></i> <?= (int) $v['fotos'] ?></span><?php endif; ?>
                    </div>
                </div>
                <?php if ($v['estado'] !== 'finalizado'): ?>
                    <span class="badge <?= Relevamiento::ESTADOS[$v['estado']][1] ?>"><?= Relevamiento::ESTADOS[$v['estado']][0] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php if ($dias < 365): ?>
    <a class="btn btn-link w-100 mt-3" href="<?= url('/historial?dias=' . ($dias === 30 ? 90 : 365)) ?>">Ver más (últimos <?= $dias === 30 ? 90 : 365 ?> días)</a>
<?php endif; ?>
