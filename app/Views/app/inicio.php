<?php $localId = $local ? (int) $local['id'] : 0; ?>
<div id="anotador" data-local="<?= $localId ?>" data-auto-detectar="<?= $autoDetectar ? '1' : '0' ?>"
     data-detectar-url="<?= url('/locales/detectar') ?>" data-buscar-url="<?= url('/fechas/productos') ?>"
     data-codigo-url="<?= url('/productos/codigo') ?>" data-asociar-url="<?= url('/productos/{id}/codigo') ?>"
     data-guardar-url="<?= url('/fechas') ?>" data-inicio-url="<?= url('/') ?>">

    <section class="anot-locales">
        <div class="anot-locales-head">
            <h2 class="section-title m-0">¿Dónde estás?</h2>
            <button type="button" class="btn btn-sm btn-link text-decoration-none" data-detectar><i class="bi bi-crosshair"></i> Detectar</button>
        </div>
        <p class="anot-gps" data-detectar-estado role="status" hidden></p>
        <div class="anot-chips">
            <?php foreach ($locales as $l): ?>
                <?php $c = $cuenta[(int) $l['id']] ?? null; ?>
                <a class="anot-chip<?= (int) $l['id'] === $localId ? ' active' : '' ?>" href="<?= url('/?local=' . (int) $l['id']) ?>">
                    <?= e($l['nombre']) ?>
                    <?php if ($c): ?><span class="anot-chip-n<?= $c['urgentes'] ? ' urgente' : '' ?>" data-chip-n><?= $c['total'] ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            <a class="anot-chip anot-chip-nuevo" href="<?= url('/locales/crear') ?>"><i class="bi bi-plus-lg"></i> Local</a>
        </div>
    </section>

    <?php if ($local === null): ?>
        <div class="empty-state card-soft mt-3">
            <i class="bi bi-geo-alt"></i>
            <p class="mb-0">Tocá el local donde estás (o <strong>Detectar</strong>) para anotar las fechas.</p>
        </div>
    <?php else: ?>
        <form class="card-soft anot-form" data-anotar novalidate>
            <div class="anot-titulo"><i class="bi bi-geo-alt-fill"></i> <?= e($local['nombre']) ?></div>
            <input type="hidden" name="local_id" value="<?= $localId ?>">
            <input type="hidden" name="producto_id" data-producto-id>

            <div class="search-bar">
                <div class="search-input">
                    <i class="bi bi-search"></i>
                    <input type="search" name="producto_texto" placeholder="Buscá o escribí el producto" autocomplete="off"
                           autocapitalize="off" enterkeyhint="next" aria-label="Producto" data-producto>
                </div>
                <button class="btn btn-primary btn-scan" type="button" data-escanear><i class="bi bi-upc-scan"></i><span>Escanear</span></button>
            </div>
            <div class="anot-sugerencias" data-sugerencias hidden></div>
            <p class="anot-prod-estado" data-producto-estado hidden></p>

            <div class="row g-2 mt-1">
                <div class="col-7">
                    <label class="form-label small mb-1" for="anot-fecha">Vence</label>
                    <div class="input-group">
                        <input class="form-control anot-fecha" id="anot-fecha" name="fecha" placeholder="dd/mm/aa" inputmode="numeric"
                               autocomplete="off" maxlength="10" enterkeyhint="done" data-fecha required>
                        <button class="btn btn-outline-secondary" type="button" data-calendario aria-label="Elegir en el calendario"><i class="bi bi-calendar3"></i></button>
                    </div>
                    <input type="date" class="anot-picker" tabindex="-1" aria-hidden="true" data-fecha-picker>
                </div>
                <div class="col-5">
                    <label class="form-label small mb-1" for="anot-cant">Cantidad</label>
                    <input class="form-control" id="anot-cant" name="cantidad" type="number" min="0" max="99999" inputmode="numeric" placeholder="—">
                </div>
                <div class="col-12">
                    <input class="form-control" name="nota" maxlength="120" placeholder="Nota (opcional): depósito, heladera 2…" aria-label="Nota">
                </div>
            </div>

            <button class="btn btn-primary btn-xl w-100 mt-3" type="submit" data-guardar><i class="bi bi-check-lg me-1"></i> Anotar</button>
            <p class="anot-form-estado" data-form-estado role="status"></p>
        </form>

        <h2 class="section-title">Fechas en <?= e($local['nombre']) ?></h2>
        <div data-lista><?= partial('fechas-lista', ['fechas' => $fechas, 'conLocal' => false]) ?></div>
    <?php endif; ?>
</div>
