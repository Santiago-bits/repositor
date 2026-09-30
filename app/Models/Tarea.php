<?php
declare(strict_types=1);

namespace App\Models;

final class Tarea extends Model
{
    /** Orden y color de cada prioridad (🔴 alta, 🟡 media, 🟢 baja). */
    public const PRIORIDADES = [
        'alta'  => ['orden' => 0, 'etiqueta' => 'Alta'],
        'media' => ['orden' => 1, 'etiqueta' => 'Media'],
        'baja'  => ['orden' => 2, 'etiqueta' => 'Baja'],
    ];

    /** tipo => [etiqueta, ícono] */
    public const TIPOS = [
        'reposicion'         => ['Reposición', 'bi-box-seam'],
        'vencimientos'       => ['Control de vencimientos', 'bi-calendar-event'],
        'conteo_promociones' => ['Conteo de promociones', 'bi-megaphone'],
        'fotos'              => ['Fotografías', 'bi-camera'],
        'otra'               => ['Otra', 'bi-list-check'],
    ];

    public const PERIODICIDADES = [
        'cada_visita' => 'Cada visita',
        'diaria'      => 'Todos los días',
        'semanal'     => 'Semanal',
        'mensual'     => 'Mensual',
        'unica'       => 'Una sola vez',
    ];

    public const DIAS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    private const COLUMNS = 't.id, t.nombre, t.descripcion, t.tipo, t.periodicidad, t.dias_semana, t.dia_mes, t.fecha, t.prioridad, t.activo';

    /** Tareas activas asignadas a un local (sin filtrar por fecha). */
    public static function paraLocal(int $localId): array
    {
        return self::fetchAll(
            'SELECT ' . self::COLUMNS . '
             FROM tareas t
             JOIN tarea_local tl ON tl.tarea_id = t.id AND tl.local_id = ?
             WHERE t.activo = 1 AND t.deleted_at IS NULL',
            [$localId]
        );
    }

    /** @return int[] ids de tareas ya hechas en ese local y fecha */
    public static function ejecutadas(int $localId, string $fecha): array
    {
        return array_map('intval', array_column(
            self::fetchAll('SELECT tarea_id FROM tarea_ejecuciones WHERE local_id = ? AND fecha = ?', [$localId, $fecha]),
            'tarea_id'
        ));
    }

    public static function todas(): array
    {
        return self::fetchAll(
            'SELECT ' . self::COLUMNS . ', (SELECT COUNT(*) FROM tarea_local tl WHERE tl.tarea_id = t.id) AS locales
             FROM tareas t WHERE t.deleted_at IS NULL
             ORDER BY t.activo DESC, FIELD(t.prioridad, \'alta\', \'media\', \'baja\'), t.nombre'
        );
    }

    public static function find(int $id): ?array
    {
        return self::fetch('SELECT ' . self::COLUMNS . ' FROM tareas t WHERE t.id = ? AND t.deleted_at IS NULL', [$id]);
    }

    public static function create(array $d): int
    {
        return self::insert(
            'INSERT INTO tareas (nombre, descripcion, tipo, periodicidad, dias_semana, dia_mes, fecha, prioridad, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            self::params($d)
        );
    }

    public static function update(int $id, array $d): void
    {
        self::execute(
            'UPDATE tareas SET nombre = ?, descripcion = ?, tipo = ?, periodicidad = ?, dias_semana = ?, dia_mes = ?,
                    fecha = ?, prioridad = ?, activo = ?
             WHERE id = ?',
            [...self::params($d), $id]
        );
    }

    public static function eliminar(int $id): void
    {
        self::execute('UPDATE tareas SET deleted_at = NOW(), activo = 0 WHERE id = ?', [$id]);
    }

    /** @return int[] */
    public static function localIds(int $id): array
    {
        return array_map('intval', array_column(
            self::fetchAll('SELECT local_id FROM tarea_local WHERE tarea_id = ?', [$id]),
            'local_id'
        ));
    }

    public static function syncLocales(int $id, array $localIds): void
    {
        self::execute('DELETE FROM tarea_local WHERE tarea_id = ?', [$id]);
        foreach ($localIds as $localId) {
            self::execute('INSERT INTO tarea_local (tarea_id, local_id) VALUES (?, ?)', [$id, $localId]);
        }
    }

    public static function marcar(int $tareaId, int $localId, int $relevamientoId, int $userId, string $fecha): void
    {
        self::execute(
            'INSERT IGNORE INTO tarea_ejecuciones (tarea_id, local_id, relevamiento_id, user_id, fecha) VALUES (?, ?, ?, ?, ?)',
            [$tareaId, $localId, $relevamientoId, $userId, $fecha]
        );
    }

    public static function desmarcar(int $tareaId, int $localId, string $fecha): void
    {
        self::execute('DELETE FROM tarea_ejecuciones WHERE tarea_id = ? AND local_id = ? AND fecha = ?', [$tareaId, $localId, $fecha]);
    }

    private static function params(array $d): array
    {
        return [
            $d['nombre'], $d['descripcion'], $d['tipo'], $d['periodicidad'], $d['dias_semana'],
            $d['dia_mes'], $d['fecha'], $d['prioridad'], (int) $d['activo'],
        ];
    }
}
