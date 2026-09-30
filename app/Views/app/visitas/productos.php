<a class="back-link" href="<?= url('/visitas/' . $visita['id']) ?>"><i class="bi bi-arrow-left"></i> Volver a la visita</a>

<div class="page-head">
    <div class="min-w-0">
        <h1>Registrar productos</h1>
        <div class="small text-body-secondary text-truncate"><i class="bi bi-geo-alt"></i> <?= e($visita['local']) ?></div>
    </div>
</div>

<form class="search-bar" method="get" action="<?= url('/visitas/' . $visita['id'] . '/productos') ?>" role="search">
    <div class="search-input">
        <i class="bi bi-search"></i>
        <input type="search" id="buscar-producto" name="q" placeholder="Buscar otro producto"
               autocomplete="off" autocapitalize="off" enterkeyhint="search" aria-label="Buscar producto"
               data-endpoint="<?= url('/visitas/' . $visita['id'] . '/buscar') ?>"
               data-codigo-endpoint="<?= url('/productos/codigo') ?>"
               data-producto-url="<?= url('/visitas/' . $visita['id'] . '/productos/{id}') ?>"
               data-crear-extra="&visita=<?= (int) $visita['id'] ?>">
    </div>
    <button class="btn btn-primary btn-scan" type="button" data-escanear>
        <i class="bi bi-upc-scan"></i><span>Escanear</span>
    </button>
</form>

<div id="resultados" class="mt-3" aria-live="polite">
    <?= partial('visita-productos-lista', ['visita' => $visita, 'productos' => $productos]) ?>
</div>
