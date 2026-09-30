<?php
use App\Models\Local;

$abiertaHoy = $abierta && $abierta['fecha'] === date('Y-m-d');
?>

<section class="hero">
    <p class="hero-date"><?= e(dia_semana()) ?> <?= date('d/m') ?></p>
    <h1>Hola, <?= e($user['nombre']) ?> 👋</h1>
</section>

<?php if ($abierta): ?>
    <div class="card-soft visita-activa mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="item-icon"><i class="bi <?= Local::ICONOS[$abierta['tipo']] ?>"></i></div>
            <div class="min-w-0 flex-grow-1">
                <div class="small fw-semibold text-primary"><span class="pulse-dot"></span> Visita en curso</div>
                <div class="fw-bold fs-5 text-truncate"><?= e($abierta['local']) ?></div>
                <div class="small text-body-secondary">
                    <?= $abiertaHoy ? 'Desde las ' . fecha($abierta['inicio_at'], 'H:i') : 'Abierta desde el ' . fecha($abierta['inicio_at'], 'd/m H:i') ?>
                </div>
            </div>
        </div>
        <?php if (!$abiertaHoy): ?>
            <p class="small text-warning-emphasis mt-3 mb-0"><i class="bi bi-exclamation-triangle"></i> Quedó abierta de un día anterior. Finalizala para empezar la de hoy.</p>
        <?php endif; ?>
        <a class="btn btn-primary btn-xl w-100 mt-3" href="<?= url('/visitas/' . $abierta['id']) ?>">
            <?= $abiertaHoy ? 'Continuar visita' : 'Ver y finalizar' ?> <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
<?php endif; ?>

<section id="deteccion" class="card-soft deteccion mb-4"
         data-endpoint="<?= url('/locales/detectar') ?>"
         data-visitas="<?= url('/visitas') ?>"
         data-abierta-local="<?= $abierta ? (int) $abierta['local_id'] : '' ?>"
         data-abierta-hoy="<?= $abiertaHoy ? '1' : '' ?>"
         data-abierta-nombre="<?= $abierta ? e($abierta['local']) : '' ?>"
         data-abierta-url="<?= $abierta ? url('/visitas/' . $abierta['id']) : '' ?>"
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
                    <div class="small text-body-secondary"><?= fecha($v['inicio_at'], 'H:i') ?> · <?= duracion($v['inicio_at'], $v['fin_at']) ?> · <?= (int) $v['productos'] ?> productos</div>
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

<?php if (is_admin()): ?>
    <a class="btn btn-outline-primary btn-xl w-100 mt-4 d-lg-none" href="<?= url('/admin') ?>">
        <i class="bi bi-speedometer2 me-1"></i> Panel de administración
    </a>
<?php endif; ?>
