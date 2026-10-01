<?php
use App\Models\Local;
use App\Models\Producto;
use App\Services\VencimientoService;

$abiertaHoy = $abierta && $abierta['fecha'] === date('Y-m-d');
?>

<section class="hero">
    <p class="hero-date"><?= e(dia_semana()) ?> <?= date('d/m') ?></p>
    <h1>Hola, <?= e($user['nombre']) ?> 👋</h1>
</section>

<?php if ($abierta): ?>
    <a class="card-soft visita-activa mb-4 d-flex align-items-center gap-3 text-reset text-decoration-none" id="visita-activa"
       href="<?= url('/visitas/' . $abierta['id']) ?>">
        <div class="item-icon"><i class="bi <?= Local::ICONOS[$abierta['tipo']] ?>"></i></div>
        <div class="min-w-0 flex-grow-1">
            <div class="small text-body-secondary">Estás en</div>
            <div class="fw-bold fs-5 text-truncate"><?= e($abierta['local']) ?></div>
        </div>
        <i class="bi bi-chevron-right text-body-secondary"></i>
    </a>
<?php endif; ?>

<section id="deteccion" class="card-soft deteccion mb-4"
         data-endpoint="<?= url('/locales/detectar') ?>"
         data-visitas="<?= url('/visitas') ?>"
         data-abierta-local="<?= $abierta ? (int) $abierta['local_id'] : '' ?>"
         data-abierta-hoy="<?= $abiertaHoy ? '1' : '' ?>"
         data-abierta-nombre="<?= $abierta ? e($abierta['local']) : '' ?>"
         data-abierta-url="<?= $abierta ? url('/visitas/' . $abierta['id']) : '' ?>"
         data-abierta-cerrar="<?= $abierta ? url('/visitas/' . $abierta['id'] . '/finalizar') : '' ?>"
         aria-live="polite">
    <div class="deteccion-cargando">
        <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
        <span>Buscando el local…</span>
    </div>
</section>

<?php if ($hoy !== []): ?>
    <h2 class="section-title">Hoy</h2>
    <div class="card-soft mb-2">
        <?php foreach ($hoy as $v): ?>
            <a class="historial-row text-reset text-decoration-none" href="<?= url('/visitas/' . $v['id']) ?>">
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate"><i class="bi bi-check-circle-fill text-success"></i> <?= e($v['local']) ?></div>
                    <div class="small text-body-secondary"><?= (int) $v['productos'] ?> productos</div>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        <?php endforeach; ?>
    </div>
    <a class="btn btn-outline-primary w-100 mb-2" href="<?= url('/mensaje') ?>"><i class="bi bi-chat-square-text"></i> Generar mensaje del día</a>
<?php endif; ?>

<h2 class="section-title" id="locales">Tus locales</h2>

<?php if ($locales === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-shop"></i>
        <?php if (is_admin()): ?>
            <p>Todavía no hay locales cargados.</p>
            <a class="btn btn-primary btn-xl" href="<?= url('/admin/locales/crear') ?>"><i class="bi bi-plus-lg me-1"></i> Crear local</a>
        <?php else: ?>
            <p class="mb-0">Todavía no tenés locales asignados. Pedile al administrador que te asigne.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($locales as $local): ?>
            <div class="item-card">
                <div class="item-icon"><i class="bi <?= Local::ICONOS[$local['tipo']] ?>"></i></div>
                <div class="item-body">
                    <div class="item-title"><?= e($local['nombre']) ?></div>
                    <div class="item-sub"><?= e($local['direccion'] ?: Local::TIPOS[$local['tipo']]) ?></div>
                </div>
                <?php if ($abiertaHoy && (int) $abierta['local_id'] === (int) $local['id']): ?>
                    <a class="btn btn-primary btn-sm" href="<?= url('/visitas/' . $abierta['id']) ?>">Continuar</a>
                <?php else: ?>
                    <?= partial('form-ingresar', ['localId' => (int) $local['id'], 'abierta' => $abierta, 'clase' => 'btn btn-outline-primary btn-sm', 'texto' => 'Ingresar']) ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2 class="section-title" id="fechas-cortas">Fechas cortas <span class="text-lowercase">(próximos <?= (int) $diasCortas ?> días)</span></h2>
<?php if ($cortas === []): ?>
    <p class="small text-body-secondary"><i class="bi bi-check-circle text-success"></i> No hay nada por vencer. Se cargan con el botón «Vencimientos» en la visita.</p>
<?php else: ?>
    <?php
    $fila = function (array $v): string {
        $estado = VencimientoService::estado($v['fecha_vencimiento']);
        $detalle = array_filter([$v['local'], $v['cantidad'] !== null ? (int) $v['cantidad'] . ' u.' : null, $v['nota']]);
        $nombre = Producto::nombreCompleto($v);
        return '<div class="corta-row">'
            . '<div class="corta-fecha ' . $estado['clase'] . '">' . fecha($v['fecha_vencimiento'], 'd/m') . '</div>'
            . '<a class="min-w-0 flex-grow-1 text-reset text-decoration-none" href="' . url('/productos/' . (int) $v['producto_id']) . '">'
            . '<div class="fw-semibold text-truncate">' . e($nombre) . '</div>'
            . '<div class="small text-body-secondary text-truncate">' . e(implode(' · ', $detalle)) . '</div>'
            . '<span class="venc-badge ' . $estado['clase'] . '">' . e($estado['etiqueta']) . '</span></a>'
            . '<form method="post" action="' . url('/vencimientos/' . (int) $v['id'] . '/retirar') . '"'
            . ' data-confirm="' . e("¿Ya retiraste {$nombre} de {$v['local']}?") . '">' . csrf_field()
            . '<button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-box-arrow-up"></i> Retirar</button></form>'
            . '</div>';
    };
    $primeras = array_slice($cortas, 0, 6);
    $resto = array_slice($cortas, 6);
    ?>
    <div class="card-soft">
        <?php foreach ($primeras as $v): ?><?= $fila($v) ?><?php endforeach; ?>
        <?php if ($resto !== []): ?>
            <details class="corta-mas">
                <summary>Ver <?= count($resto) ?> más</summary>
                <?php foreach ($resto as $v): ?><?= $fila($v) ?><?php endforeach; ?>
            </details>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (is_admin()): ?>
    <a class="btn btn-outline-primary btn-xl w-100 mt-4 d-lg-none" href="<?= url('/admin') ?>">
        <i class="bi bi-gear me-1"></i> Gestión (locales, productos, promos…)
    </a>
<?php endif; ?>
