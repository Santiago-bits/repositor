<?php
use App\Models\Local;
use App\Models\Relevamiento;

$id = (int) $m['id'];
$numeros = [
    ['bi-geo-alt', 'Visitas', $m['visitas']],
    ['bi-shop', 'Locales', $m['locales']],
    ['bi-cart-x', 'Faltantes', $m['faltantes']],
    ['bi-calendar-x', 'Vencimientos', $m['vencimientos']],
    ['bi-camera', 'Fotos', $m['fotos']],
];
?>
<a class="back-link" href="<?= url('/admin/equipo?dias=' . $dias) ?>"><i class="bi bi-arrow-left"></i> Equipo</a>

<div class="card-soft p-3 mb-3 d-flex align-items-center gap-3">
    <div class="item-avatar"><?= e(initials($m)) ?></div>
    <div class="min-w-0 flex-grow-1">
        <h1 class="h5 fw-bold mb-0"><?= e($m['nombre'] . ' ' . $m['apellido']) ?></h1>
        <div class="small text-body-secondary"><?= e($m['role_nombre']) ?> · activo <?= e(hace($m['last_seen_at'])) ?></div>
        <?php if (!$m['activo']): ?><span class="badge text-bg-secondary">Desactivado</span><?php endif; ?>
    </div>
    <a class="btn btn-sm btn-outline-primary" href="<?= url("/admin/usuarios/{$id}/editar") ?>" aria-label="Editar usuario"><i class="bi bi-pencil"></i></a>
</div>

<?= partial('equipo-periodo', ['dias' => $dias, 'periodos' => $periodos, 'base' => "/admin/equipo/{$id}"]) ?>

<?php if ($m['role_slug'] === 'vendedor'): ?>
    <div class="notice mt-3">
        <i class="bi bi-briefcase"></i>
        <div>Los vendedores no hacen visitas: consultan los faltantes, las fechas cortas y los productos.
            Última vez que abrió la app: <strong><?= e(hace($m['last_seen_at'])) ?></strong>.</div>
    </div>
<?php else: ?>
    <div class="equipo-numeros my-3">
        <?php foreach ($numeros as [$icono, $etiqueta, $valor]): ?>
            <div class="card-soft">
                <i class="bi <?= $icono ?>"></i>
                <strong><?= (int) $valor ?></strong>
                <span><?= $etiqueta ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex gap-2 mb-2">
        <a class="btn btn-outline-primary flex-fill" href="<?= url('/admin/fotos?user_id=' . $id . '&desde=' . date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'))) ?>"><i class="bi bi-images"></i> Sus fotos</a>
    </div>

    <?php if ($porFecha === []): ?>
        <div class="empty-state card-soft">
            <i class="bi bi-clock-history"></i>
            <p class="mb-0">Sin visitas en este período<?= $m['ultima_visita'] ? '. La última fue el ' . e(fecha($m['ultima_visita'], 'd/m')) : '' ?>.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($porFecha as $fecha => $visitas): ?>
        <h2 class="section-title"><?= e(dia_semana(strtotime($fecha))) ?> <?= fecha($fecha, 'd/m') ?></h2>
        <div class="item-list">
            <?php foreach ($visitas as $v): ?>
                <a class="item-card" href="<?= url('/visitas/' . $v['id']) ?>">
                    <div class="item-icon"><i class="bi <?= Local::ICONOS[$v['tipo']] ?>"></i></div>
                    <div class="item-body">
                        <div class="item-title"><?= e($v['local']) ?></div>
                        <div class="item-sub">
                            <?= fecha($v['inicio_at'], 'H:i') ?><?= $v['fin_at'] ? ' a ' . fecha($v['fin_at'], 'H:i') : '' ?>
                            · <?= e(Relevamiento::actividad($v)) ?>
                        </div>
                        <?php if ($v['fotos'] > 0): ?><div class="item-meta"><span><i class="bi bi-camera"></i> <?= (int) $v['fotos'] ?></span></div><?php endif; ?>
                    </div>
                    <i class="bi bi-chevron-right text-body-secondary"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
