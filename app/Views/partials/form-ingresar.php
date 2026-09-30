<?php
/** Botón "Ingresar" a un local. Recibe $localId, $clase, $texto. Si había otra visita abierta, se cierra sola. */
?>
<form method="post" action="<?= url('/visitas') ?>" data-ingresar>
    <?= csrf_field() ?>
    <input type="hidden" name="local_id" value="<?= $localId ?>">
    <input type="hidden" name="lat_inicio" value="">
    <input type="hidden" name="lng_inicio" value="">
    <button class="<?= e($clase) ?>" type="submit"><?= e($texto) ?></button>
</form>
