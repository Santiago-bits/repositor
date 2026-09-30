<?php
declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;
use App\Models\Tarea;

final class TareaRequest
{
    /** @return array{0: array, 1: array} [datos limpios, errores] */
    public static function validate(array $input): array
    {
        $v = new Validator($input, [
            'nombre'       => 'required|max:100',
            'descripcion'  => 'nullable|max:255',
            'tipo'         => 'required|in:' . implode(',', array_keys(Tarea::TIPOS)),
            'periodicidad' => 'required|in:' . implode(',', array_keys(Tarea::PERIODICIDADES)),
            'prioridad'    => 'required|in:' . implode(',', array_keys(Tarea::PRIORIDADES)),
            'dia_mes'      => 'nullable|integer|between:1,31',
            'fecha'        => 'nullable',
            'activo'       => 'boolean',
        ], [
            'nombre'       => 'nombre',
            'descripcion'  => 'descripción',
            'tipo'         => 'tipo',
            'periodicidad' => 'periodicidad',
            'prioridad'    => 'prioridad',
            'dia_mes'      => 'El día del mes',
            'fecha'        => 'fecha',
        ]);

        $data = $v->validated();
        $errors = $v->errors();

        $dias = array_values(array_unique(array_filter(
            array_map('intval', (array) ($input['dias_semana'] ?? [])),
            fn ($d) => $d >= 1 && $d <= 7
        )));
        sort($dias);

        // Cada periodicidad usa solo su dato; el resto se limpia.
        $periodicidad = $data['periodicidad'] ?? null;
        $data['dias_semana'] = $periodicidad === 'semanal' ? implode(',', $dias) : null;
        if ($periodicidad !== 'mensual') {
            $data['dia_mes'] = null;
        }
        if ($periodicidad !== 'unica') {
            $data['fecha'] = null;
        }

        if ($periodicidad === 'semanal' && $dias === []) {
            $errors['dias_semana'] = 'Elegí al menos un día de la semana.';
        }
        if ($periodicidad === 'mensual' && empty($data['dia_mes']) && !isset($errors['dia_mes'])) {
            $errors['dia_mes'] = 'Indicá qué día del mes.';
        }
        if ($periodicidad === 'unica' && !fecha_valida((string) $data['fecha'])) {
            $errors['fecha'] = 'Indicá la fecha de la tarea.';
        }

        $data['locales'] = array_values(array_unique(array_filter(
            array_map('intval', (array) ($input['locales'] ?? []))
        )));

        return [$data, $errors];
    }
}
