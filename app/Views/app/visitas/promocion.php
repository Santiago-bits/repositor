<?php
use App\Models\Producto;

$vid = (int) $visita['id'];

// Los que ya están en promo van arriba, en "En promo"; el resto, abajo.
$fila = function (array $p, int $orden, bool $marcadoYa) use ($enPromo): string {
    $id = (int) $p['id'];
    $stock = isset($enPromo[$id]) && $p['rp_id'] !== null ? (string) $p['stock'] : '';
    $nombre = Producto::nombreCompleto($p);
    $texto = mb_strtolower($nombre . ' ' . $p['marca']);

    return '<label class="promo-fila" data-orden="' . $orden . '" data-texto="' . e($texto) . '">'
        . '<input class="form-check-input" type="checkbox" name="promo[' . $id . ']" value="1"' . ($marcadoYa ? ' checked' : '')
        . ' aria-label="En promo: ' . e($nombre) . '">'
        . '<span class="flex-grow-1 min-w-0"><span class="d-block fw-semibold">' . e($nombre) . '</span>'
        . ($p['marca'] ? '<span class="small text-body-secondary">' . e($p['marca']) . '</span>' : '') . '</span>'
        . '<input class="form-control promo-stock" type="number" name="stock[' . $id . ']" value="' . e($stock) . '"'
        . ' min="0" max="99999" inputmode="numeric" placeholder="Stock" data-auto-marcar aria-label="Stock de ' . e($nombre) . '">'
        . '</label>';
};

$marcados = '';
$resto = '';
$cantidad = 0;
foreach ($productos as $i => $p) {
    $esPromo = isset($enPromo[(int) $p['id']]) || $marcado === (int) $p['id'];
    if ($esPromo) {
        $marcados .= $fila($p, $i, true);
        $cantidad++;
    } else {
        $resto .= $fila($p, $i, false);
    }
}
?>
<a class="back-link" href="<?= url("/visitas/{$vid}") ?>"><i class="bi bi-arrow-left"></i> Volver a la visita</a>

<div class="page-head">
    <div class="min-w-0">
        <h1>Promos del finde</h1>
        <div class="small text-body-secondary text-truncate"><i class="bi bi-geo-alt"></i> <?= e($visita['local']) ?></div>
    </div>
</div>

<?php if ($productos === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-box-seam"></i>
        <p>Todavía no hay productos cargados.</p>
        <a class="btn btn-outline-primary" href="<?= url("/visitas/{$vid}/productos") ?>">Buscar o escanear productos</a>
    </div>
<?php else: ?>
    <form method="post" action="<?= url("/visitas/{$vid}/promociones") ?>" data-promos>
        <?= csrf_field() ?>

        <div class="search-input mb-3">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Buscar bebida…" autocomplete="off" autocapitalize="off" enterkeyhint="search"
                   aria-label="Buscar producto" data-promo-buscar>
        </div>

        <?php // Resultados: solo aparecen mientras se escribe en el buscador. ?>
        <div class="card-soft promo-grupo mb-3" data-promo-lista hidden><?= $resto ?></div>
        <p class="small text-body-secondary" data-promo-sin-resultados hidden>No hay productos con ese nombre.</p>

        <h2 class="section-title mt-0">En promo (<span data-promo-contador><?= $cantidad ?></span>)</h2>
        <div class="card-soft promo-grupo" data-promo-marcados><?= $marcados ?></div>
        <p class="small text-body-secondary mb-0" data-promo-vacio <?= $cantidad ? 'hidden' : '' ?>>Todavía no marcaste ninguno. Buscá el producto arriba y tocalo.</p>

        <a class="small d-inline-block mt-2" href="<?= url("/visitas/{$vid}/productos") ?>"><i class="bi bi-upc-scan"></i> ¿Falta un producto? Buscalo o escanealo</a>

        <div class="form-actions flex-column">
            <button class="btn btn-primary btn-xl" type="submit" name="mensaje" value="0"><i class="bi bi-check-lg me-1"></i> GUARDAR</button>
            <button class="btn btn-outline-primary" type="submit" name="mensaje" value="1"><i class="bi bi-chat-square-text me-1"></i> Guardar y armar mensaje</button>
        </div>
    </form>
<?php endif; ?>
