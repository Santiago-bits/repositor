<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Tarea;

/**
 * Qué tareas corresponden a un local en una fecha.
 * No hace falta un cron: se calcula a partir de la periodicidad de cada tarea.
 */
final class TareaService
{
    /** @return array<int, array> tareas del día con 'hecha' => bool, ordenadas por prioridad */
    public static function delDia(int $localId, ?string $fecha = null): array
    {
        $fecha ??= date('Y-m-d');
        $hechas = Tarea::ejecutadas($localId, $fecha);

        $tareas = array_values(array_filter(
            Tarea::paraLocal($localId),
            fn (array $t) => self::corresponde($t, $fecha)
        ));

        foreach ($tareas as &$t) {
            $t['hecha'] = in_array((int) $t['id'], $hechas, true);
        }
        unset($t);

        usort($tareas, fn ($a, $b) =>
            [$a['hecha'], Tarea::PRIORIDADES[$a['prioridad']]['orden'], $a['nombre']]
            <=> [$b['hecha'], Tarea::PRIORIDADES[$b['prioridad']]['orden'], $b['nombre']]
        );
        return $tareas;
    }

    /** Marca como hechas las tareas del día de ese tipo (ej.: al terminar el conteo de promociones). */
    public static function completarTipo(array $visita, string $tipo): void
    {
        foreach (self::delDia((int) $visita['local_id'], $visita['fecha']) as $t) {
            if ($t['tipo'] === $tipo && !$t['hecha']) {
                Tarea::marcar((int) $t['id'], (int) $visita['local_id'], (int) $visita['id'], (int) $visita['user_id'], $visita['fecha']);
            }
        }
    }

    /** "Semanal: Lun, Vie", "Mensual: día 5", "Una sola vez: 12/10/2026"… */
    public static function describir(array $t): string
    {
        return match ($t['periodicidad']) {
            'semanal' => 'Semanal: ' . implode(', ', array_map(
                fn ($d) => Tarea::DIAS[(int) $d] ?? '?',
                array_filter(explode(',', (string) $t['dias_semana']))
            )),
            'mensual' => 'Mensual: día ' . (int) $t['dia_mes'],
            'unica'   => 'Una sola vez: ' . fecha($t['fecha']),
            default   => Tarea::PERIODICIDADES[$t['periodicidad']] ?? $t['periodicidad'],
        };
    }

    public static function corresponde(array $tarea, string $fecha): bool
    {
        $ts = strtotime($fecha);

        return match ($tarea['periodicidad']) {
            'diaria', 'cada_visita' => true,
            'unica'   => $tarea['fecha'] === $fecha,
            'semanal' => in_array(date('N', $ts), explode(',', (string) $tarea['dias_semana']), true),
            // Día 31 en un mes de 30 días: se hace el último día del mes.
            'mensual' => (int) date('j', $ts) === min((int) $tarea['dia_mes'], (int) date('t', $ts)),
            default   => false,
        };
    }
}
