<?php
/** Inicio del vendedor: qué falta en cada local (para ir a venderlo) y lo que vence pronto. Solo lectura. */
use App\Models\Producto;
?>
<div class="mb-3">
    <div class="small text-primary fw-semibold"><?= e(dia_semana() . ' ' . date('d/m')) ?></div>
    <h1 class="h3 fw-bold mb-0">Hola, <?= e($user['nombre']) ?> 👋</h1>
</div>

<h2 class="section-title mt-0">Faltantes por local</h2>
<?php if ($locales === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-check2-circle"></i>
        <p class="mb-0">No hay faltantes anotados en las últimas visitas.</p>
    </div>
<?php else: ?>
    <?php foreach ($locales as $l): ?>
        <div class="card-soft p-3 mb-3">
            <div class="d-flex align-items-start gap-2 mb-2">
                <i class="bi bi-shop fs-4 text-primary"></i>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold"><?= e($l['local']) ?></div>
                    <div class="small text-body-secondary">
                        <?= e(dia_semana(strtotime($l['fecha'])) . ' ' . fecha($l['fecha'], 'd/m')) ?> · <?= e($l['repositor']) ?>
                    </div>
                </div>
                <div class="text-end small">
                    <?php if ($l['sin_stock']): ?><span class="falta-chip falta-sin"><?= (int) $l['sin_stock'] ?> sin stock</span><?php endif; ?>
                    <?php if ($l['bajo']): ?><span class="falta-chip falta-poco"><?= (int) $l['bajo'] ?> poco</span><?php endif; ?>
                </div>
            </div>
            <?php foreach ($l['categorias'] as $categoria => $productos): ?>
                <div class="small fw-semibold text-body-secondary text-uppercase mt-2 mb-1"><?= e($categoria) ?></div>
                <?php foreach ($productos as $p): ?>
                    <a class="vend-fila text-reset text-decoration-none" href="<?= url('/productos/' . (int) $p['producto_id']) ?>">
                        <?= producto_thumb($p) ?>
                        <span class="min-w-0 flex-grow-1 text-truncate"><?= e(Producto::nombreCompleto($p)) ?></span>
                        <span class="falta-chip <?= $p['estado_stock'] === 'sin_stock' ? 'falta-sin' : 'falta-poco' ?>">
                            <?= $p['estado_stock'] === 'sin_stock' ? 'Sin stock' : 'Poco' ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h2 class="section-title">Fechas cortas <span class="text-lowercase">(próximos <?= (int) $diasCortas ?> días)</span></h2>
<?php if ($cortas === []): ?>
    <p class="small text-body-secondary">Nada por vencer en los locales.</p>
<?php else: ?>
    <div class="card-soft">
        <?php foreach ($cortas as $v): ?>
            <?= partial('fecha-corta', ['v' => $v, 'soloVer' => true]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
