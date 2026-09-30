<?php
use App\Models\Tarea;
use App\Services\TareaService;
?>
<div class="page-head">
    <h1>Tareas</h1>
    <a class="btn btn-primary" href="<?= url('/admin/tareas/crear') ?>"><i class="bi bi-plus-lg"></i> Nueva</a>
</div>

<p class="small text-body-secondary">Las tareas aparecen solas en cada local el día que corresponde. No hace falta crearlas día por día.</p>

<?php if ($tareas === []): ?>
    <div class="empty-state card-soft">
        <i class="bi bi-list-check"></i>
        <p class="mb-0">Todavía no hay tareas.</p>
    </div>
<?php else: ?>
    <div class="item-list">
        <?php foreach ($tareas as $t): ?>
            <a class="item-card<?= $t['activo'] ? '' : ' is-inactive' ?>" href="<?= url('/admin/tareas/' . $t['id'] . '/editar') ?>">
                <div class="item-icon"><i class="bi <?= Tarea::TIPOS[$t['tipo']][1] ?>"></i></div>
                <div class="item-body">
                    <div class="item-title"><span class="prio-dot prio-<?= e($t['prioridad']) ?>"></span> <?= e($t['nombre']) ?></div>
                    <div class="item-sub"><?= e(TareaService::describir($t)) ?></div>
                    <div class="item-meta">
                        <span><i class="bi bi-shop"></i> <?= (int) $t['locales'] ?> locales</span>
                        <?php if (!$t['activo']): ?><span class="badge text-bg-secondary">Inactiva</span><?php endif; ?>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
