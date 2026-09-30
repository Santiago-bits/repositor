<?php
use App\Models\Producto;

$vid = (int) $visita['id'];
?>
<a class="back-link" href="<?= url("/visitas/{$vid}") ?>"><i class="bi bi-arrow-left"></i> Volver a la visita</a>

<div class="page-head">
    <div class="min-w-0">
        <h1>Promos del finde</h1>
        <div class="small text-body-secondary text-truncate"><i class="bi bi-geo-alt"></i> <?= e($visita['local']) ?></div>
    </div>
</div>

<p class="small text-body-secondary">Marcá los productos que están en promo. Si querés, anotá el stock al lado.</p>

<?php if ($productos === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-box-seam"></i>
        <p>Este local todavía no tiene productos.</p>
        <a class="btn btn-outline-primary" href="<?= url("/visitas/{$vid}/productos") ?>">Buscar o escanear productos</a>
    </div>
<?php else: ?>
    <form method="post" action="<?= url("/visitas/{$vid}/promociones") ?>">
        <?= csrf_field() ?>
        <div class="card-soft">
            <?php foreach ($productos as $p): ?>
                <?php
                $id = (int) $p['id'];
                $enPromoYa = isset($enPromo[$id]);
                $stock = $enPromoYa && $p['rp_id'] !== null ? $p['stock'] : '';
                ?>
                <label class="promo-fila">
                    <input class="form-check-input" type="checkbox" name="promo[<?= $id ?>]" value="1"
                           <?= $enPromoYa || $marcado === $id ? 'checked' : '' ?> aria-label="En promo: <?= e(Producto::nombreCompleto($p)) ?>">
                    <span class="flex-grow-1 min-w-0">
                        <span class="d-block fw-semibold"><?= e(Producto::nombreCompleto($p)) ?></span>
                        <?php if ($p['marca']): ?><span class="small text-body-secondary"><?= e($p['marca']) ?></span><?php endif; ?>
                    </span>
                    <input class="form-control promo-stock" type="number" name="stock[<?= $id ?>]" value="<?= e($stock ?? '') ?>"
                           min="0" max="99999" inputmode="numeric" placeholder="Stock" data-auto-marcar aria-label="Stock">
                </label>
            <?php endforeach; ?>
        </div>

        <a class="small d-inline-block mt-2" href="<?= url("/visitas/{$vid}/productos") ?>"><i class="bi bi-upc-scan"></i> ¿Falta un producto? Buscalo o escanealo</a>

        <div class="form-actions flex-column">
            <button class="btn btn-primary btn-xl" type="submit" name="mensaje" value="0"><i class="bi bi-check-lg me-1"></i> GUARDAR</button>
            <button class="btn btn-outline-primary" type="submit" name="mensaje" value="1"><i class="bi bi-chat-square-text me-1"></i> Guardar y armar mensaje</button>
        </div>
    </form>
<?php endif; ?>
