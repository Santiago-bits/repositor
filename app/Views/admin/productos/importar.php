<a class="back-link" href="<?= url('/admin/productos') ?>"><i class="bi bi-arrow-left"></i> Productos</a>

<div class="page-head">
    <h1>Subir Excel</h1>
</div>

<?php if ($resultado !== null): ?>
    <div class="card-soft p-3 mb-3">
        <div class="fw-semibold mb-2"><i class="bi bi-check-circle text-success"></i> Listo</div>
        <ul class="list-unstyled mb-0 small">
            <li><strong><?= (int) $resultado['creados'] ?></strong> productos nuevos</li>
            <li><strong><?= (int) $resultado['actualizados'] ?></strong> actualizados</li>
            <li><strong><?= (int) $resultado['sin_cambios'] ?></strong> ya estaban igual</li>
            <?php if ($resultado['total_errores']): ?>
                <li class="text-danger"><strong><?= (int) $resultado['total_errores'] ?></strong> filas con problemas (no se cargaron)</li>
            <?php endif; ?>
        </ul>
        <?php if ($resultado['errores'] !== []): ?>
            <div class="import-errores mt-2">
                <?php foreach ($resultado['errores'] as $fila => $error): ?>
                    <div class="small"><span class="text-body-secondary">Fila <?= (int) $fila ?>:</span> <?= e($error) ?></div>
                <?php endforeach; ?>
                <?php if ($resultado['total_errores'] > count($resultado['errores'])): ?>
                    <div class="small text-body-secondary">…y <?= $resultado['total_errores'] - count($resultado['errores']) ?> más.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <a class="btn btn-outline-primary btn-sm mt-3" href="<?= url('/admin/productos') ?>">Ver productos</a>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/admin/productos/importar') ?>" enctype="multipart/form-data" class="card-soft p-3 mb-3">
    <?= csrf_field() ?>
    <label class="form-label" for="archivo">Archivo de Excel (.xlsx) o .csv</label>
    <input class="form-control mb-3" type="file" id="archivo" name="archivo" required
           accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv">
    <button class="btn btn-primary btn-xl w-100" type="submit"><i class="bi bi-upload me-1"></i> Subir productos</button>
</form>

<h2 class="section-title">Cómo tiene que estar el Excel</h2>
<div class="card-soft p-3 small">
    <p class="mb-2">En la <strong>primera fila</strong>, los títulos de las columnas. Solo <strong>Nombre</strong> es obligatorio:</p>
    <div class="table-responsive mb-2">
        <table class="table table-sm mb-0">
            <thead><tr><th>Nombre</th><th>Marca</th><th>Presentación</th><th>Código</th><th>Categoría</th><th>Subcategoría</th></tr></thead>
            <tbody>
                <tr><td>Fernet Branca</td><td>Branca</td><td>750 ml</td><td>779…</td><td>Bebidas con alcohol</td><td>Aperitivos</td></tr>
                <tr><td>Coca-Cola</td><td>Coca-Cola</td><td>2,25 L</td><td></td><td>Bebidas sin alcohol</td><td>Gaseosas</td></tr>
            </tbody>
        </table>
    </div>
    <ul class="mb-2 ps-3">
        <li>Si un producto ya existe (mismo código, o mismo nombre, marca y presentación) se <strong>actualiza</strong>, no se duplica. Podés subir el mismo Excel las veces que quieras.</li>
        <li>Las celdas vacías no borran lo que ya estaba cargado.</li>
        <li>Las categorías que no existan se crean solas. La unidad (ml, cc, L) se saca de la presentación.</li>
    </ul>
    <a href="<?= url('/admin/productos/plantilla') ?>"><i class="bi bi-download"></i> Descargar plantilla de ejemplo</a>
</div>
