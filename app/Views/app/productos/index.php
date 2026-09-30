<div class="page-head">
    <h1>Productos</h1>
    <a class="btn btn-link" href="<?= url('/productos/crear') ?>"><i class="bi bi-plus-lg"></i> Nuevo</a>
</div>

<form class="search-bar" method="get" action="<?= url('/productos') ?>" role="search" data-busqueda>
    <div class="search-input">
        <i class="bi bi-search"></i>
        <input type="search" id="buscar-producto" name="q" value="<?= e($q) ?>" placeholder="Buscar producto"
               autocomplete="off" autocapitalize="off" enterkeyhint="search" aria-label="Buscar producto"
               data-endpoint="<?= url('/productos/buscar') ?>" data-codigo-endpoint="<?= url('/productos/codigo') ?>"
               <?= $q === '' ? 'autofocus' : '' ?>>
    </div>
    <button class="btn btn-primary btn-scan" type="button" data-escanear>
        <i class="bi bi-upc-scan"></i><span>Escanear</span>
    </button>
</form>

<div id="resultados" class="mt-3" aria-live="polite">
    <?= partial('productos-resultados', ['q' => $q, 'resultados' => $resultados]) ?>
</div>
