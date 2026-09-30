<?php
/** Botones para sacar o elegir fotos. Recibe $visitaId y $productoId (null = foto general). */
?>
<div class="foto-acciones" data-foto-subir
     data-endpoint="<?= url("/visitas/{$visitaId}/fotos") ?>"
     data-producto="<?= $productoId ? (int) $productoId : '' ?>"
     data-scope="<?= $productoId ? 'producto' : 'visita' ?>"
     data-destino="#fotos-lista" data-estado="#foto-estado">
    <label class="btn btn-primary">
        <i class="bi bi-camera-fill"></i> Sacar foto
        <input type="file" accept="image/*" capture="environment" hidden data-foto-input>
    </label>
    <label class="btn btn-outline-primary">
        <i class="bi bi-images"></i> Galería
        <input type="file" accept="image/*" multiple hidden data-foto-input>
    </label>
</div>
<div id="foto-estado" class="mt-2"></div>
