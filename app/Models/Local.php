<?php
declare(strict_types=1);

namespace App\Models;

final class Local extends Model
{
    public const TIPOS = [
        'supermercado' => 'Supermercado',
        'chino'        => 'Chino',
        'autoservicio' => 'Autoservicio',
        'mayorista'    => 'Mayorista',
        'otro'         => 'Otro',
    ];

    public const ICONOS = [
        'supermercado' => 'bi-cart3',
        'chino'        => 'bi-shop',
        'autoservicio' => 'bi-basket',
        'mayorista'    => 'bi-boxes',
        'otro'         => 'bi-geo-alt',
    ];

    private const COLUMNS = 'id, nombre, tipo, direccion, latitud, longitud, radio_m, telefono, contacto, activo, es_prueba, observaciones, created_at';

    public static function all(?string $buscar = null): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM locales WHERE deleted_at IS NULL';
        $params = [];
        if ($buscar !== null && $buscar !== '') {
            $sql .= ' AND (nombre LIKE ? OR direccion LIKE ?)';
            $params = ["%{$buscar}%", "%{$buscar}%"];
        }
        return self::fetchAll($sql . ' ORDER BY activo DESC, nombre', $params);
    }

    /** Locales que puede trabajar el usuario: el admin ve todos, el repositor solo los asignados. */
    /** Los locales son de todos (cualquiera puede crearlos y visitarlos); lo que se carga en cada visita es de cada uno. */
    public static function paraUsuario(array $user): array
    {
        return self::fetchAll('SELECT ' . self::COLUMNS . ' FROM locales WHERE activo = 1 AND deleted_at IS NULL ORDER BY nombre');
    }

    /** Local activo que el usuario puede trabajar (cualquiera de los activos). */
    public static function accesible(array $user, int $id): ?array
    {
        return self::fetch('SELECT ' . self::COLUMNS . ' FROM locales WHERE id = ? AND activo = 1 AND deleted_at IS NULL', [$id]);
    }

    public static function find(int $id): ?array
    {
        return self::fetch('SELECT ' . self::COLUMNS . ' FROM locales WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    /** @param int[] $ids  @return int[] los que existen */
    public static function existingIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        return array_map('intval', array_column(
            self::fetchAll("SELECT id FROM locales WHERE deleted_at IS NULL AND id IN ({$marks})", array_values($ids)),
            'id'
        ));
    }

    public static function create(array $d): int
    {
        return self::insert(
            'INSERT INTO locales (nombre, tipo, direccion, latitud, longitud, radio_m, telefono, contacto, observaciones, activo, es_prueba)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            self::params($d)
        );
    }

    public static function update(int $id, array $d): void
    {
        self::execute(
            'UPDATE locales SET nombre = ?, tipo = ?, direccion = ?, latitud = ?, longitud = ?, radio_m = ?,
                    telefono = ?, contacto = ?, observaciones = ?, activo = ?, es_prueba = ?
             WHERE id = ?',
            [...self::params($d), $id]
        );
    }

    /** Se oculta (no se borra la fila) para conservar el historial de visitas. */
    public static function eliminar(int $id): void
    {
        self::execute('UPDATE locales SET deleted_at = NOW(), activo = 0 WHERE id = ?', [$id]);
    }

    public static function setActivo(int $id, bool $activo): void
    {
        self::execute('UPDATE locales SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }

    private static function params(array $d): array
    {
        return [
            $d['nombre'], $d['tipo'], $d['direccion'], $d['latitud'], $d['longitud'], $d['radio_m'],
            $d['telefono'], $d['contacto'], $d['observaciones'], (int) $d['activo'], (int) $d['es_prueba'],
        ];
    }
}
