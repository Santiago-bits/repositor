<?php
use App\Models\Local;

$diasCortos = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'];
$t = $semana['totales'];
?>

<div class="page-head">
    <h1>Resumen</h1>
    <span class="text-body-secondary small"><?= e(dia_semana()) ?> <?= date('d/m') ?></span>
</div>

<div class="row g-2 mb-2">
    <?php foreach ($tarjetas as $c): ?>
        <div class="col-4">
            <a class="stat-card stat-card-sm<?= $c['tone'] ? ' tone-' . $c['tone'] : '' ?>" href="<?= url($c['href']) ?>">
                <div class="stat-icon"><i class="bi <?= $c['icon'] ?>"></i></div>
                <div class="stat-value"><?= number_format($c['value'], 0, ',', '.') ?></div>
                <div class="stat-label"><?= e($c['label']) ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<h2 class="section-title">Tu semana <span class="text-lowercase">(últimos 7 días)</span></h2>
<div class="card-soft p-3">
    <div class="semana-totales">
        <div><strong><?= (int) $t['visitas'] ?></strong><span>visitas</span></div>
        <div><strong><?= (int) $t['locales'] ?></strong><span>locales</span></div>
        <div><strong><?= (int) $t['productos'] ?></strong><span>productos</span></div>
        <div><strong><?= (int) $t['fotos'] ?></strong><span>fotos</span></div>
    </div>
    <div class="semana-barras" role="img" aria-label="Visitas por día en los últimos 7 días">
        <?php foreach ($semana['dias'] as $d): ?>
            <?php $hoy = $d['fecha'] === date('Y-m-d'); ?>
            <div class="semana-dia<?= $hoy ? ' is-hoy' : '' ?>">
                <span class="semana-num"><?= $d['visitas'] ?: '' ?></span>
                <div class="semana-barra" style="height: <?= max(4, (int) round($d['visitas'] * 100 / $semana['max'])) ?>%"></div>
                <span class="semana-etq"><?= $diasCortos[(int) date('w', strtotime($d['fecha']))] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($olvidados !== []): ?>
    <h2 class="section-title">Hace mucho que no vas</h2>
    <div class="card-soft">
        <?php foreach ($olvidados as $l): ?>
            <div class="corta-row">
                <div class="item-icon item-icon-sm"><i class="bi <?= Local::ICONOS[$l['tipo']] ?>"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold text-truncate"><?= e($l['nombre']) ?></div>
                    <div class="small text-body-secondary">
                        <?php if ($l['ultima'] === null): ?>
                            Nunca visitado
                        <?php else: ?>
                            <?php $hace = (int) round((strtotime(date('Y-m-d')) - strtotime($l['ultima'])) / 86400); ?>
                            Última visita hace <?= $hace ?> días (<?= fecha($l['ultima'], 'd/m') ?>)
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($faltantes['sin_stock'] !== [] || $faltantes['locales'] !== []): ?>
    <div class="row g-3">
        <?php if ($faltantes['sin_stock'] !== []): ?>
            <div class="col-md-6">
                <h2 class="section-title">Más veces sin stock <span class="text-lowercase">(30 días)</span></h2>
                <div class="card-soft px-3">
                    <?php foreach ($faltantes['sin_stock'] as $i => $p): ?>
                        <a class="ranking-row text-reset text-decoration-none" href="<?= url('/productos/' . (int) $p['id']) ?>">
                            <span class="ranking-pos"><?= $i + 1 ?></span>
                            <span class="flex-grow-1 min-w-0 text-truncate"><?= e(trim($p['nombre'] . ' ' . $p['presentacion'])) ?></span>
                            <strong><?= (int) $p['veces'] ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($faltantes['locales'] !== []): ?>
            <div class="col-md-6">
                <h2 class="section-title">Locales con más faltantes <span class="text-lowercase">(30 días)</span></h2>
                <div class="card-soft px-3">
                    <?php foreach ($faltantes['locales'] as $i => $l): ?>
                        <div class="ranking-row">
                            <span class="ranking-pos"><?= $i + 1 ?></span>
                            <span class="flex-grow-1 min-w-0 text-truncate"><?= e($l['nombre']) ?></span>
                            <span class="small text-nowrap"><span class="text-danger fw-semibold"><?= (int) $l['sin_stock'] ?></span> sin stock · <?= (int) $l['bajo'] ?> bajo</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h2 class="section-title">Últimas visitas</h2>
<?php if ($actividad === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-clipboard"></i>
        <p class="mb-0">Todavía no hay visitas. Aparecen acá cuando entres a un local.</p>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($actividad as $a): ?>
            <a class="item-card" href="<?= url('/visitas/' . (int) $a['id']) ?>">
                <div class="item-icon"><i class="bi <?= Local::ICONOS[$a['tipo']] ?>"></i></div>
                <div class="item-body">
                    <div class="item-title"><?= e($a['local']) ?></div>
                    <div class="item-sub">
                        <?= $a['fecha'] === date('Y-m-d') ? 'Hoy' : fecha($a['fecha'], 'd/m') ?>
                        · <?= (int) $a['productos'] ?> producto<?= (int) $a['productos'] === 1 ? '' : 's' ?>
                        <?php if ($a['fotos'] > 0): ?>· <i class="bi bi-camera"></i> <?= (int) $a['fotos'] ?><?php endif; ?>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="d-grid gap-2 mt-4">
    <a class="btn btn-primary btn-xl" href="<?= url('/mensaje') ?>"><i class="bi bi-chat-square-text me-1"></i> Mensaje del día</a>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary flex-fill" href="<?= url('/admin/productos/importar') ?>"><i class="bi bi-file-earmark-excel"></i> Subir Excel</a>
        <a class="btn btn-outline-primary flex-fill" href="<?= url('/admin/locales/crear') ?>"><i class="bi bi-plus-lg"></i> Nuevo local</a>
    </div>
</div>
