<?php
/** Tareas del día dentro de la visita. Recibe $visita, $tareas, $editable y $pendientes (promos sin contar). */
use App\Models\Tarea;

$vid = (int) $visita['id'];
?>
<?php if ($tareas === []): ?>
    <p class="small text-body-secondary mb-0">No hay tareas para hoy en este local.</p>
<?php else: ?>
    <div class="card-soft">
        <?php foreach ($tareas as $t): ?>
            <?php
            $destino = match ($t['tipo']) {
                'conteo_promociones'         => ["/visitas/{$vid}/conteo", 'Contar'],
                'reposicion', 'vencimientos' => ["/visitas/{$vid}/productos", 'Ir'],
                'fotos'                      => ["/visitas/{$vid}#fotos", 'Ir'],
                default                      => null,
            };
            ?>
            <div class="tarea-row<?= $t['hecha'] ? ' is-hecha' : '' ?>">
                <?php if ($editable): ?>
                    <button type="button" class="tarea-check" data-accion="<?= url("/visitas/{$vid}/tareas/{$t['id']}") ?>" data-destino="#tareas-lista"
                            aria-label="<?= $t['hecha'] ? 'Marcar como pendiente' : 'Marcar como hecha' ?>: <?= e($t['nombre']) ?>">
                        <i class="bi <?= $t['hecha'] ? 'bi-check-circle-fill text-success' : 'bi-circle' ?>"></i>
                    </button>
                <?php elseif ($t['hecha']): ?>
                    <i class="bi bi-check-circle-fill text-success"></i>
                <?php endif; ?>
                <?php if (!$t['hecha']): ?><span class="prio-dot prio-<?= e($t['prioridad']) ?>" title="Prioridad <?= e($t['prioridad']) ?>"></span><?php endif; ?>
                <span class="flex-grow-1">
                    <?= e($t['nombre']) ?>
                    <?php if ($t['tipo'] === 'conteo_promociones' && $pendientes > 0 && !$t['hecha']): ?>
                        <span class="badge text-bg-danger"><?= $pendientes ?> para contar</span>
                    <?php endif; ?>
                </span>
                <?php if ($editable && $destino && !$t['hecha']): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= url($destino[0]) ?>"><?= $destino[1] ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
