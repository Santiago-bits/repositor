<?php
use App\Models\Local;
use App\Models\Relevamiento;
?>

<div class="page-head">
    <h1>Relevamientos</h1>
</div>

<form class="card-soft p-3 mb-3" method="get" action="<?= url('/admin/relevamientos') ?>">
    <div class="row g-2">
        <div class="col-6 col-md-3">
            <label class="form-label small" for="desde">Desde</label>
            <input class="form-control" type="date" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="hasta">Hasta</label>
            <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="local_id">Local</label>
            <select class="form-select" id="local_id" name="local_id">
                <option value="">Todos</option>
                <?php foreach ($locales as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= (int) $filtros['local_id'] === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="user_id">Usuario</label>
            <select class="form-select" id="user_id" name="user_id">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (int) $filtros['user_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['nombre'] . ' ' . $u['apellido']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-8 col-md-2">
            <label class="form-label small" for="estado">Estado</label>
            <select class="form-select" id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (Relevamiento::ESTADOS as $valor => [$texto]): ?>
                    <option value="<?= $valor ?>" <?= $filtros['estado'] === $valor ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4 col-md-12 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit" style="min-height:48px"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
    </div>
</form>

<p class="text-body-secondary small"><?= count($relevamientos) ?> relevamiento<?= count($relevamientos) === 1 ? '' : 's' ?> entre el <?= fecha($filtros['desde']) ?> y el <?= fecha($filtros['hasta']) ?></p>

<?php if ($relevamientos === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-clipboard"></i>
        <p class="mb-0">No hay relevamientos con esos filtros.</p>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($relevamientos as $r): ?>
            <?php [$texto, $clase] = Relevamiento::ESTADOS[$r['estado']]; ?>
            <a class="item-card" href="<?= url('/visitas/' . $r['id']) ?>">
                <div class="item-icon"><i class="bi <?= Local::ICONOS[$r['tipo']] ?>"></i></div>
                <div class="item-body">
                    <div class="item-title"><?= e($r['local']) ?></div>
                    <div class="item-sub"><?= fecha($r['fecha'], 'd/m') ?> · <?= e($r['usuario']) ?></div>
                    <div class="item-meta">
                        <span><i class="bi bi-box-seam"></i> <?= (int) $r['productos'] ?> productos</span>
                    </div>
                </div>
                <span class="badge <?= $clase ?>"><?= $texto ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
