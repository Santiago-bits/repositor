<?php use App\Models\Local; ?>

<div class="page-head">
    <h1>Locales</h1>
    <a class="btn btn-primary" href="<?= url('/admin/locales/crear') ?>"><i class="bi bi-plus-lg"></i> Nuevo</a>
</div>

<form method="get" action="<?= url('/admin/locales') ?>" class="mb-3" role="search">
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input class="form-control" type="search" name="q" value="<?= e($buscar) ?>" placeholder="Buscar por nombre o dirección">
    </div>
</form>

<?php if ($locales === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-shop"></i>
        <p class="mb-0"><?= $buscar !== '' ? 'No hay locales que coincidan con la búsqueda.' : 'Todavía no hay locales cargados.' ?></p>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($locales as $l): ?>
            <a class="item-card<?= $l['activo'] ? '' : ' is-inactive' ?>" href="<?= url("/admin/locales/{$l['id']}/editar") ?>">
                <div class="item-icon"><i class="bi <?= Local::ICONOS[$l['tipo']] ?>"></i></div>
                <div class="item-body">
                    <div class="item-title"><?= e($l['nombre']) ?></div>
                    <div class="item-sub">
                        <?= e(Local::TIPOS[$l['tipo']]) ?>
                        <?php if ($l['direccion']): ?> · <?= e($l['direccion']) ?><?php endif; ?>
                    </div>
                    <div class="item-meta">
                        <?php if ($l['latitud'] !== null && $l['cerca_de'] !== []): ?>
                            <span class="text-warning-emphasis"><i class="bi bi-exclamation-triangle"></i> Misma ubicación que <?= e(implode(', ', $l['cerca_de'])) ?>: revisala</span>
                        <?php elseif ($l['latitud'] !== null): ?>
                            <span><i class="bi bi-geo-alt"></i> Radio <?= (int) $l['radio_m'] ?> m</span>
                        <?php else: ?>
                            <span class="text-warning-emphasis"><i class="bi bi-geo"></i> Sin ubicación</span>
                        <?php endif; ?>
                        <?php if ($l['es_prueba']): ?><span class="badge badge-muted">Prueba</span><?php endif; ?>
                        <?php if (!$l['activo']): ?><span class="badge text-bg-secondary">Inactivo</span><?php endif; ?>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
