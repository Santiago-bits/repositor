<?php
$iconos = ['success' => 'bi-check-circle-fill', 'error' => 'bi-exclamation-triangle-fill', 'warning' => 'bi-exclamation-triangle-fill', 'info' => 'bi-info-circle-fill'];
foreach (App\Core\Session::getFlash('messages', []) as $m):
    $tipo = $m['type'] === 'error' ? 'danger' : $m['type'];
?>
    <div class="alert alert-<?= e($tipo) ?> alert-dismissible fade show d-flex gap-2 align-items-start" role="alert" data-autohide="<?= $m['type'] === 'success' ? '1' : '0' ?>">
        <i class="bi <?= $iconos[$m['type']] ?? 'bi-info-circle-fill' ?>"></i>
        <div><?= e($m['text']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endforeach; ?>
