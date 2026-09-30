<div class="page-head">
    <h1>Fotos</h1>
</div>

<form class="card-soft p-3 mb-3" method="get" action="<?= url('/admin/fotos') ?>">
    <div class="row g-2">
        <div class="col-6 col-md-3">
            <label class="form-label small" for="desde">Desde</label>
            <input class="form-control" type="date" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="hasta">Hasta</label>
            <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
        </div>
        <div class="col-8 col-md-4">
            <label class="form-label small" for="local_id">Local</label>
            <select class="form-select" id="local_id" name="local_id">
                <option value="">Todos</option>
                <?php foreach ($locales as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= (int) $filtros['local_id'] === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4 col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit" style="min-height:48px"><i class="bi bi-funnel"></i></button>
        </div>
    </div>
</form>

<p class="small text-body-secondary"><?= count($fotos) ?> foto<?= count($fotos) === 1 ? '' : 's' ?></p>

<?php if ($fotos === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-images"></i>
        <p class="mb-0">No hay fotos en ese período.</p>
    </div>
<?php else: ?>
    <div class="foto-grid foto-grid-lg">
        <?php foreach ($fotos as $f): ?>
            <figure class="foto-item">
                <a href="<?= url('/fotos/' . $f['id']) ?>" target="_blank" rel="noopener">
                    <img src="<?= url('/fotos/' . $f['id']) ?>" alt="Foto en <?= e($f['local']) ?>" loading="lazy">
                </a>
                <figcaption>
                    <a class="text-reset" href="<?= url('/visitas/' . $f['relevamiento_id']) ?>"><?= e($f['local']) ?></a>
                    · <?= fecha($f['created_at'], 'd/m H:i') ?><br>
                    <?= $f['producto'] ? e(trim($f['producto'] . ' ' . $f['presentacion'])) : 'General' ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
