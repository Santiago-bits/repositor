<?php
use App\Models\Producto;

$vid = (int) $visita['id'];
$total = count($items);
?>
<a class="back-link" href="<?= url("/visitas/{$vid}") ?>"><i class="bi bi-arrow-left"></i> Volver a la visita</a>

<div class="page-head">
    <div class="min-w-0">
        <h1>Conteo de promociones</h1>
        <div class="small text-body-secondary text-truncate"><i class="bi bi-geo-alt"></i> <?= e($visita['local']) ?></div>
    </div>
</div>

<?php if ($total === 0): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-megaphone"></i>
        <p>No hay promociones para contar hoy en este local.</p>
        <a class="btn btn-outline-primary" href="<?= url("/visitas/{$vid}/promociones/crear") ?>"><i class="bi bi-plus-lg"></i> Registrar promoción</a>
    </div>
<?php elseif ($actual): ?>
    <form class="card-soft conteo-card mb-3" method="post" action="<?= url("/visitas/{$vid}/conteo/{$actual['promo_id']}") ?>">
        <?= csrf_field() ?>
        <div class="conteo-paso"><?= $actual['posicion'] ?>/<?= $total ?></div>
        <h2 class="conteo-producto"><?= e(Producto::nombreCompleto($actual)) ?></h2>
        <p class="small text-body-secondary mb-3">
            <?= $actual['marca'] ? e($actual['marca']) . ' · ' : '' ?>Promo del <?= fecha($actual['fecha_inicio'], 'd/m') ?> al <?= fecha($actual['fecha_fin'], 'd/m') ?>
        </p>

        <label class="form-label fw-semibold" for="stock">Stock encontrado</label>
        <div class="stepper">
            <button type="button" data-paso="-1" aria-label="Restar uno"><i class="bi bi-dash-lg"></i></button>
            <input id="stock" name="stock" type="number" inputmode="numeric" pattern="[0-9]*" min="0" max="99999" required
                   value="<?= e($actual['stock'] ?? '') ?>" placeholder="0" autofocus>
            <button type="button" data-paso="1" aria-label="Sumar uno"><i class="bi bi-plus-lg"></i></button>
        </div>

        <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" role="switch" id="no_exhibido" name="no_exhibido" value="1" <?= ($actual['estado_stock'] ?? '') === 'no_exhibido' ? 'checked' : '' ?>>
            <label class="form-check-label" for="no_exhibido">No estaba exhibido</label>
        </div>

        <button class="btn btn-primary btn-xl w-100 mt-3" type="submit">GUARDAR Y SIGUIENTE <i class="bi bi-arrow-right ms-1"></i></button>
    </form>
<?php else: ?>
    <div class="empty-state card-soft conteo-listo mb-3">
        <i class="bi bi-check-circle-fill text-success"></i>
        <p class="fw-bold fs-5 mb-1">CONTEO COMPLETADO</p>
        <p><?= $contados ?>/<?= $total ?> productos registrados.</p>
        <div class="d-grid gap-2">
            <a class="btn btn-primary btn-xl" href="<?= url('/mensaje?fecha=' . $visita['fecha'] . '&tipo=promos') ?>"><i class="bi bi-chat-square-text me-1"></i> GENERAR MENSAJE</a>
            <a class="btn btn-outline-primary" href="<?= url("/visitas/{$vid}") ?>">Ver resumen de la visita</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($total > 0): ?>
    <h2 class="section-title">Productos a contar (<?= $contados ?>/<?= $total ?>)</h2>
    <div class="card-soft">
        <?php foreach ($items as $item): ?>
            <a class="conteo-fila text-reset text-decoration-none<?= $actual && (int) $actual['promo_id'] === (int) $item['promo_id'] ? ' is-actual' : '' ?>"
               href="<?= url("/visitas/{$vid}/conteo?promo={$item['promo_id']}") ?>">
                <i class="bi <?= $item['rp_id'] !== null ? 'bi-check-square-fill text-success' : 'bi-square text-body-secondary' ?>"></i>
                <span class="flex-grow-1"><?= e(Producto::nombreCompleto($item)) ?></span>
                <?php if ($item['rp_id'] !== null): ?><strong><?= $item['stock'] !== null ? (int) $item['stock'] : '—' ?></strong><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
