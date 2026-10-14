<?php /** Selector Hoy / 7 días / 30 días. Recibe $dias, $periodos y $base (URL sin ?dias). */ ?>
<div class="btn-group w-100 mb-2" role="group" aria-label="Período">
    <?php foreach ($periodos as $n => $etiqueta): ?>
        <a class="btn btn-outline-primary<?= $n === $dias ? ' active' : '' ?>" href="<?= url($base . '?dias=' . $n) ?>"><?= e($etiqueta) ?></a>
    <?php endforeach; ?>
</div>
