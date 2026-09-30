<?php use App\Models\Local; ?>

<div class="page-head">
    <h1>Dashboard</h1>
    <span class="text-body-secondary small"><?= e(dia_semana()) ?> <?= date('d/m/Y') ?></span>
</div>

<div class="row g-2 g-md-3 mb-2">
    <?php foreach ($tarjetas as $t): ?>
        <div class="col-6 col-md-4">
            <?php $tag = $t['href'] ? 'a' : 'div'; ?>
            <<?= $tag ?> class="stat-card<?= $t['tone'] ? ' tone-' . $t['tone'] : '' ?>"<?= $t['href'] ? ' href="' . url($t['href']) . '"' : '' ?>>
                <div class="stat-icon"><i class="bi <?= $t['icon'] ?>"></i></div>
                <div class="stat-value"><?= number_format($t['value'], 0, ',', '.') ?></div>
                <div class="stat-label"><?= e($t['label']) ?></div>
            </<?= $tag ?>>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-primary" href="<?= url('/admin/locales/crear') ?>"><i class="bi bi-plus-lg"></i> Nuevo local</a>
    <a class="btn btn-outline-primary" href="<?= url('/admin/usuarios/crear') ?>"><i class="bi bi-person-plus"></i> Nuevo usuario</a>
</div>

<?php if ($stats['vencimientos'] !== []): ?>
    <h2 class="section-title">Vencimientos próximos</h2>
    <div class="card-soft px-3 mb-2">
        <?php foreach ($stats['vencimientos'] as $v): ?>
            <?php $estado = App\Services\VencimientoService::estado($v['fecha_vencimiento']); ?>
            <div class="venc-row">
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= e(trim($v['nombre'] . ' ' . $v['presentacion'])) ?></div>
                    <div class="small text-body-secondary"><?= e($v['local']) ?> · <?= fecha($v['fecha_vencimiento']) ?></div>
                </div>
                <span class="venc-badge <?= $estado['clase'] ?>"><?= e($estado['etiqueta']) ?></span>
                <div class="venc-cant"><?= $v['cantidad'] !== null ? (int) $v['cantidad'] : '—' ?> <small>u.</small></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($stats['sin_stock'] !== [] || $stats['locales'] !== []): ?>
    <div class="row g-3">
        <?php if ($stats['sin_stock'] !== []): ?>
            <div class="col-md-6">
                <h2 class="section-title">Más veces sin stock <span class="text-lowercase">(30 días)</span></h2>
                <div class="card-soft px-3">
                    <?php foreach ($stats['sin_stock'] as $i => $p): ?>
                        <div class="ranking-row"><span class="ranking-pos"><?= $i + 1 ?></span><span class="flex-grow-1"><?= e(trim($p['nombre'] . ' ' . $p['presentacion'])) ?></span><strong><?= (int) $p['veces'] ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($stats['locales'] !== []): ?>
            <div class="col-md-6">
                <h2 class="section-title">Locales con más faltantes <span class="text-lowercase">(30 días)</span></h2>
                <div class="card-soft px-3">
                    <?php foreach ($stats['locales'] as $i => $l): ?>
                        <div class="ranking-row">
                            <span class="ranking-pos"><?= $i + 1 ?></span>
                            <span class="flex-grow-1"><?= e($l['nombre']) ?> <span class="small text-body-secondary">· <?= (int) $l['visitas'] ?> visitas</span></span>
                            <span class="small"><span class="prio-dot prio-alta"></span> <?= (int) $l['sin_stock'] ?> <span class="prio-dot prio-media ms-2"></span> <?= (int) $l['bajo'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h2 class="section-title">Actividad reciente</h2>

<?php if ($actividad === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-clipboard"></i>
        <p class="mb-0">Todavía no hay relevamientos. Van a aparecer acá cuando empiecen las visitas (Etapa 3).</p>
    </div>
<?php else: ?>
    <?php $estados = ['en_proceso' => ['En proceso', 'text-bg-warning'], 'finalizado' => ['Finalizado', 'text-bg-success'], 'cancelado' => ['Cancelado', 'text-bg-secondary']]; ?>
    <div class="item-list">
        <?php foreach ($actividad as $a): ?>
            <div class="item-card">
                <div class="item-icon"><i class="bi <?= Local::ICONOS[$a['tipo']] ?>"></i></div>
                <div class="item-body">
                    <div class="item-title"><?= e($a['local']) ?></div>
                    <div class="item-sub"><?= fecha($a['inicio_at'], 'd/m H:i') ?> · <?= e($a['usuario']) ?> · <?= (int) $a['productos'] ?> productos</div>
                </div>
                <span class="badge <?= $estados[$a['estado']][1] ?>"><?= $estados[$a['estado']][0] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
