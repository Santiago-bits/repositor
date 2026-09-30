<?php
declare(strict_types=1);

namespace App\Models;

/** Promociones: un producto, en uno o varios locales, entre dos fechas. */
final class Promocion extends Model
{
    public const ESTADOS = [
        'activa'     => 'Activa',
        'finalizada' => 'Finalizada',
        'cancelada'  => 'Cancelada',
    ];

    private const SELECT = "SELECT p.id, p.producto_id, p.fecha_inicio, p.fecha_fin, p.precio_normal, p.precio_promo,
                                   p.observaciones, p.estado, p.created_by, pr.nombre, pr.presentacion, pr.marca,
                                   CONCAT(u.nombre, ' ', u.apellido) AS creador,
                                   (SELECT GROUP_CONCAT(l.nombre ORDER BY l.nombre SEPARATOR ', ')
                                      FROM promocion_local pl JOIN locales l ON l.id = pl.local_id
                                     WHERE pl.promocion_id = p.id AND l.deleted_at IS NULL) AS locales
                            FROM promociones p
                            JOIN productos pr ON pr.id = p.producto_id
                            LEFT JOIN users u ON u.id = p.created_by";

    public static function find(int $id): ?array
    {
        return self::fetch(self::SELECT . ' WHERE p.id = ? AND p.deleted_at IS NULL', [$id]);
    }

    /** $filtro: vigentes | proximas | terminadas | todas */
    public static function listar(string $filtro, ?int $localId = null): array
    {
        $sql = self::SELECT . ' WHERE p.deleted_at IS NULL';
        $sql .= match ($filtro) {
            'vigentes'   => " AND p.estado = 'activa' AND CURDATE() BETWEEN p.fecha_inicio AND p.fecha_fin",
            'proximas'   => " AND p.estado = 'activa' AND p.fecha_inicio > CURDATE()",
            'terminadas' => " AND (p.estado <> 'activa' OR p.fecha_fin < CURDATE())",
            default      => '',
        };
        $params = [];
        if ($localId) {
            $sql .= ' AND EXISTS (SELECT 1 FROM promocion_local pl WHERE pl.promocion_id = p.id AND pl.local_id = ?)';
            $params[] = $localId;
        }
        return self::fetchAll($sql . ' ORDER BY p.fecha_fin DESC, pr.nombre LIMIT 300', $params);
    }

    /** Promos activas de un local en una fecha, indexadas por producto_id. */
    public static function vigentesEnLocal(int $localId, string $fecha): array
    {
        $filas = self::fetchAll(
            self::SELECT . " WHERE p.deleted_at IS NULL AND p.estado = 'activa' AND ? BETWEEN p.fecha_inicio AND p.fecha_fin
               AND EXISTS (SELECT 1 FROM promocion_local pl WHERE pl.promocion_id = p.id AND pl.local_id = ?)
             ORDER BY pr.nombre, pr.presentacion",
            [$fecha, $localId]
        );
        return array_column($filas, null, 'producto_id');
    }

    /** Otra promo activa del mismo producto que se superpone en fechas y locales. */
    public static function solapada(int $productoId, array $localIds, string $desde, string $hasta, ?int $exceptId = null): ?array
    {
        if ($localIds === []) {
            return null;
        }
        $marcas = implode(',', array_fill(0, count($localIds), '?'));
        return self::fetch(
            "SELECT p.id, p.fecha_inicio, p.fecha_fin, l.nombre AS local
             FROM promociones p
             JOIN promocion_local pl ON pl.promocion_id = p.id
             JOIN locales l ON l.id = pl.local_id
             WHERE p.producto_id = ? AND p.deleted_at IS NULL AND p.estado = 'activa' AND p.id <> ?
               AND p.fecha_inicio <= ? AND p.fecha_fin >= ? AND pl.local_id IN ({$marcas})
             LIMIT 1",
            [$productoId, $exceptId ?? 0, $hasta, $desde, ...$localIds]
        );
    }

    public static function create(array $d, int $userId): int
    {
        return self::insert(
            'INSERT INTO promociones (producto_id, fecha_inicio, fecha_fin, precio_normal, precio_promo, observaciones, estado, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['producto_id'], $d['fecha_inicio'], $d['fecha_fin'], $d['precio_normal'], $d['precio_promo'],
             $d['observaciones'], $d['estado'] ?? 'activa', $userId]
        );
    }

    public static function update(int $id, array $d): void
    {
        self::execute(
            'UPDATE promociones SET producto_id = ?, fecha_inicio = ?, fecha_fin = ?, precio_normal = ?, precio_promo = ?,
                    observaciones = ?, estado = ?
             WHERE id = ?',
            [$d['producto_id'], $d['fecha_inicio'], $d['fecha_fin'], $d['precio_normal'], $d['precio_promo'],
             $d['observaciones'], $d['estado'] ?? 'activa', $id]
        );
    }

    /** @return int[] */
    public static function localIds(int $id): array
    {
        return array_map('intval', array_column(
            self::fetchAll('SELECT local_id FROM promocion_local WHERE promocion_id = ?', [$id]),
            'local_id'
        ));
    }

    public static function syncLocales(int $id, array $localIds): void
    {
        self::execute('DELETE FROM promocion_local WHERE promocion_id = ?', [$id]);
        foreach ($localIds as $localId) {
            self::execute('INSERT INTO promocion_local (promocion_id, local_id) VALUES (?, ?)', [$id, $localId]);
        }
    }

    public static function eliminar(int $id): void
    {
        self::execute('UPDATE promociones SET deleted_at = NOW() WHERE id = ?', [$id]);
    }

    public static function setEstado(int $id, string $estado): void
    {
        self::execute('UPDATE promociones SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    /** Cómo mostrarla hoy: [etiqueta, clase de badge]. */
    public static function situacion(array $p): array
    {
        $hoy = date('Y-m-d');
        return match (true) {
            $p['estado'] === 'cancelada'  => ['Cancelada', 'text-bg-danger'],
            $p['estado'] === 'finalizada' => ['Finalizada', 'text-bg-secondary'],
            $p['fecha_fin'] < $hoy        => ['Terminada', 'text-bg-secondary'],
            $p['fecha_inicio'] > $hoy     => ['Próxima', 'text-bg-info'],
            default                       => ['Vigente', 'text-bg-success'],
        };
    }
}
