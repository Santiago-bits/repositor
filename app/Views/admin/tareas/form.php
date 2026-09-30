<?php
use App\Models\Tarea;

$v = fn (string $campo, mixed $default = '') => old($campo, $tarea[$campo] ?? $default);
$activo = has_old() ? (bool) old('activo', false) : (bool) ($tarea['activo'] ?? true);
$tipoActual = $v('tipo', 'otra');
$prioridadActual = $v('prioridad', 'media');
$periodicidadActual = $v('periodicidad', 'cada_visita');
$diasActuales = array_map('intval', (array) old('dias_semana', array_filter(explode(',', (string) ($tarea['dias_semana'] ?? '')))));
$marcados = array_map('intval', (array) old('locales', $asignados));
?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <a class="btn btn-link" href="<?= url('/admin/tareas') ?>">Cancelar</a>
</div>

<form method="post" action="<?= url($tarea ? '/admin/tareas/' . $tarea['id'] : '/admin/tareas') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label" for="nombre">Nombre</label>
        <input class="form-control<?= invalid('nombre') ?>" id="nombre" name="nombre" value="<?= e($v('nombre')) ?>" maxlength="100" required placeholder="Ej: Conteo de promociones">
        <?= field_error('nombre') ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="descripcion">Descripción <span class="text-body-secondary">(opc.)</span></label>
        <input class="form-control<?= invalid('descripcion') ?>" id="descripcion" name="descripcion" value="<?= e($v('descripcion')) ?>" maxlength="255">
        <?= field_error('descripcion') ?>
    </div>

    <div class="mb-3">
        <span class="form-label d-block">Tipo</span>
        <div class="option-grid">
            <?php foreach (Tarea::TIPOS as $valor => [$etiqueta, $icono]): ?>
                <input type="radio" class="btn-check" name="tipo" id="tipo-<?= $valor ?>" value="<?= $valor ?>" <?= $tipoActual === $valor ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="tipo-<?= $valor ?>"><i class="bi <?= $icono ?>"></i> <?= $etiqueta ?></label>
            <?php endforeach; ?>
        </div>
        <div class="form-text">"Conteo de promociones" se marca sola como hecha al terminar el conteo.</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="periodicidad">¿Cuándo?</label>
        <select class="form-select" id="periodicidad" name="periodicidad">
            <?php foreach (Tarea::PERIODICIDADES as $valor => $etiqueta): ?>
                <option value="<?= $valor ?>" <?= $periodicidadActual === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3" data-mostrar-si="periodicidad:semanal">
        <span class="form-label d-block">Días</span>
        <div class="dias-grid">
            <?php foreach (Tarea::DIAS as $n => $dia): ?>
                <input type="checkbox" class="btn-check" name="dias_semana[]" id="dia-<?= $n ?>" value="<?= $n ?>" <?= in_array($n, $diasActuales, true) ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="dia-<?= $n ?>"><?= $dia ?></label>
            <?php endforeach; ?>
        </div>
        <?= field_error('dias_semana') ?>
    </div>

    <div class="mb-3" data-mostrar-si="periodicidad:mensual">
        <label class="form-label" for="dia_mes">Día del mes</label>
        <input class="form-control<?= invalid('dia_mes') ?>" type="number" id="dia_mes" name="dia_mes" min="1" max="31" value="<?= e($v('dia_mes')) ?>" inputmode="numeric">
        <?= field_error('dia_mes') ?>
        <div class="form-text">Si el mes tiene menos días, se hace el último día.</div>
    </div>

    <div class="mb-3" data-mostrar-si="periodicidad:unica">
        <label class="form-label" for="fecha">Fecha</label>
        <input class="form-control<?= invalid('fecha') ?>" type="date" id="fecha" name="fecha" value="<?= e($v('fecha')) ?>">
        <?= field_error('fecha') ?>
    </div>

    <div class="mb-3">
        <span class="form-label d-block">Prioridad</span>
        <div class="option-grid">
            <?php foreach (Tarea::PRIORIDADES as $valor => $info): ?>
                <input type="radio" class="btn-check" name="prioridad" id="prio-<?= $valor ?>" value="<?= $valor ?>" <?= $prioridadActual === $valor ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="prio-<?= $valor ?>"><span class="prio-dot prio-<?= $valor ?>"></span> <?= $info['etiqueta'] ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-3">
        <span class="form-label d-block">Locales</span>
        <div class="card-soft check-list">
            <?php foreach ($locales as $l): ?>
                <label class="form-check check-row">
                    <input class="form-check-input" type="checkbox" name="locales[]" value="<?= $l['id'] ?>" <?= in_array((int) $l['id'], $marcados, true) ? 'checked' : '' ?>>
                    <span class="form-check-label"><?= e($l['nombre']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card-soft px-3 py-2 mb-3">
        <div class="form-check form-switch py-2">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $activo ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Tarea activa</label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary btn-xl flex-grow-1" type="submit"><i class="bi bi-check-lg me-1"></i> Guardar</button>
    </div>
</form>

<?php if ($tarea): ?>
    <form method="post" action="<?= url('/admin/tareas/' . $tarea['id'] . '/eliminar') ?>" class="mt-3" data-confirm="¿Eliminar esta tarea? Se conserva lo que ya se registró como hecho.">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-trash3 me-1"></i> Eliminar tarea</button>
    </form>
<?php endif; ?>
