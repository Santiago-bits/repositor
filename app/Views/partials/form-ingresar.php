<?php
/** Botón "Ingresar" a un local. Recibe $localId, $abierta (visita abierta o null), $clase, $texto. */
// Hay que cerrar la abierta si es de otro local o quedó de un día anterior.
$otraAbierta = $abierta && ((int) $abierta['local_id'] !== $localId || $abierta['fecha'] !== date('Y-m-d'));
?>
<form method="post" action="<?= url('/visitas') ?>" data-ingresar
      <?php if ($otraAbierta): ?>data-confirm="Tenés una visita abierta en <?= e($abierta['local']) ?>. Se va a finalizar para ingresar a este local."<?php endif; ?>>
    <?= csrf_field() ?>
    <input type="hidden" name="local_id" value="<?= $localId ?>">
    <input type="hidden" name="lat_inicio" value="">
    <input type="hidden" name="lng_inicio" value="">
    <?php if ($otraAbierta): ?><input type="hidden" name="cerrar_anterior" value="1"><?php endif; ?>
    <button class="<?= e($clase) ?>" type="submit"><?= e($texto) ?></button>
</form>
