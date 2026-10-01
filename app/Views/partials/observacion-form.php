<?php
/** Recibe $visitaId y $productoId (null = observación general de la visita). */
?>
<form method="post" action="<?= url("/visitas/{$visitaId}/observaciones") ?>" data-obs-form data-destino="#observaciones-lista">
    <?= csrf_field() ?>
    <input type="hidden" name="producto_id" value="<?= $productoId ? (int) $productoId : '' ?>">
    <input type="hidden" name="scope" value="<?= $productoId ? 'producto' : 'visita' ?>">
    <textarea class="form-control" name="texto" rows="2" maxlength="500" placeholder="Escribí una observación" aria-label="Observación"></textarea>
    <button class="btn btn-outline-primary w-100 mt-2" type="submit"><i class="bi bi-chat-left-text"></i> Guardar observación</button>
</form>
