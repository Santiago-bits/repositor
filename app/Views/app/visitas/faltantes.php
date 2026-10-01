<?php
use App\Models\Producto;

$vid = (int) $visita['id'];

/** Fila de un faltante con "Sin stock / Poco" y el botón para quitarlo. $id puede ser el marcador __ID__ (plantilla). */
$fila = function (string $id, string $nombre, string $estado): string {
    $opcion = fn (string $valor, string $texto, string $clase) =>
        '<input type="radio" class="btn-check" name="falta[' . $id . ']" id="f-' . $id . '-' . $valor . '" value="' . $valor . '"' . ($estado === $valor ? ' checked' : '') . '>'
        . '<label class="btn btn-sm ' . $clase . '" for="f-' . $id . '-' . $valor . '">' . $texto . '</label>';

    return '<div class="falta-fila" data-id="' . $id . '">'
        . '<div class="min-w-0 flex-grow-1 fw-semibold text-truncate" data-falta-nombre>' . e($nombre) . '</div>'
        . '<div class="falta-opciones">' . $opcion('sin_stock', 'Sin stock', 'falta-sin') . $opcion('bajo', 'Poco', 'falta-poco') . '</div>'
        . '<button type="button" class="btn-icon" aria-label="Quitar" data-falta-quitar><i class="bi bi-x-lg"></i></button>'
        . '</div>';
};
?>
<a class="back-link" href="<?= url("/visitas/{$vid}") ?>"><i class="bi bi-arrow-left"></i> Volver a la visita</a>

<div class="page-head">
    <div class="min-w-0">
        <h1>Faltantes</h1>
        <div class="small text-body-secondary text-truncate"><i class="bi bi-geo-alt"></i> <?= e($visita['local']) ?></div>
    </div>
</div>

<form method="post" action="<?= url("/visitas/{$vid}/faltantes") ?>" data-faltantes>
    <?= csrf_field() ?>

    <div class="search-input mb-3">
        <i class="bi bi-search"></i>
        <input type="search" placeholder="Buscar bebida…" autocomplete="off" autocapitalize="off" enterkeyhint="search"
               aria-label="Buscar producto" data-falta-buscar>
    </div>

    <?php // Resultados: solo mientras se escribe. Tocar uno lo agrega a la lista como "Sin stock". ?>
    <div class="card-soft promo-grupo mb-3" data-falta-resultados hidden>
        <?php foreach ($productos as $p): ?>
            <button type="button" class="venc-opcion" data-id="<?= (int) $p['id'] ?>"
                    data-nombre="<?= e(Producto::nombreCompleto($p)) ?>"
                    data-texto="<?= e(mb_strtolower(Producto::nombreCompleto($p) . ' ' . $p['marca'])) ?>">
                <span class="min-w-0 flex-grow-1 text-start">
                    <span class="d-block fw-semibold"><?= e(Producto::nombreCompleto($p)) ?></span>
                    <?php if ($p['marca']): ?><span class="small text-body-secondary"><?= e($p['marca']) ?></span><?php endif; ?>
                </span>
                <i class="bi bi-plus-circle text-primary fs-5"></i>
            </button>
        <?php endforeach; ?>
    </div>
    <p class="small text-body-secondary" data-falta-sin-resultados hidden>
        No hay productos con ese nombre. <a href="<?= url('/productos/crear?visita=' . $vid) ?>">Crearlo</a>
    </p>

    <h2 class="section-title mt-0">Faltan (<span data-falta-contador><?= count($faltantes) ?></span>)</h2>
    <div class="card-soft promo-grupo" data-falta-lista>
        <?php foreach ($faltantes as $f): ?>
            <?= $fila((string) (int) $f['producto_id'], Producto::nombreCompleto($f), $f['estado_stock']) ?>
        <?php endforeach; ?>
    </div>
    <p class="small text-body-secondary mb-0" data-falta-vacio <?= $faltantes ? 'hidden' : '' ?>>
        Buscá arriba lo que no hay o hay poco, y tocalo.
    </p>

    <div class="form-actions flex-column">
        <button class="btn btn-primary btn-xl" type="submit" name="mensaje" value="0"><i class="bi bi-check-lg me-1"></i> GUARDAR</button>
        <button class="btn btn-outline-primary" type="submit" name="mensaje" value="1"><i class="bi bi-whatsapp me-1"></i> Guardar y armar lista para el vendedor</button>
    </div>

    <template data-falta-plantilla><?= $fila('__ID__', '', 'sin_stock') ?></template>
</form>
