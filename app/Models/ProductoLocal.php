<?php
declare(strict_types=1);

namespace App\Models;

/** Qué productos se trabajan en cada local, con sus datos propios (stock habitual, góndola). */
final class ProductoLocal extends Model
{
    /** @return array<int, array> filas activas indexadas por local_id */
    public static function dePorProducto(int $productoId): array
    {
        $filas = self::fetchAll(
            'SELECT local_id, stock_habitual, ubicacion_gondola FROM producto_local WHERE producto_id = ? AND activo = 1',
            [$productoId]
        );
        return array_column($filas, null, 'local_id');
    }

    /** Locales activos donde está el producto. Para el repositor, solo los asignados. */
    public static function localesDeProducto(int $productoId, array $user): array
    {
        $esAdmin = $user['role_slug'] === 'admin';
        $params = $esAdmin ? [$productoId] : [$user['id'], $productoId];

        return self::fetchAll(
            'SELECT l.id, l.nombre, l.tipo, pl.stock_habitual, pl.ubicacion_gondola
             FROM producto_local pl
             JOIN locales l ON l.id = pl.local_id AND l.activo = 1 AND l.deleted_at IS NULL
             ' . ($esAdmin ? '' : 'JOIN local_user lu ON lu.local_id = l.id AND lu.user_id = ?') . '
             WHERE pl.producto_id = ? AND pl.activo = 1
             ORDER BY l.nombre',
            $params
        );
    }

    /** Productos que se trabajan en el local, con lo ya registrado en la visita (rp_id null = pendiente). */
    public static function productosDeLocal(int $localId, int $relevamientoId): array
    {
        return self::fetchAll(
            'SELECT p.id, p.nombre, p.marca, p.presentacion, p.imagen_path, pl.ubicacion_gondola,
                    rp.id AS rp_id, rp.stock, rp.estado_stock
             FROM producto_local pl
             JOIN productos p ON p.id = pl.producto_id AND p.activo = 1 AND p.deleted_at IS NULL
             LEFT JOIN relevamiento_productos rp ON rp.producto_id = p.id AND rp.relevamiento_id = ?
             WHERE pl.local_id = ? AND pl.activo = 1
             ORDER BY p.nombre, p.presentacion',
            [$relevamientoId, $localId]
        );
    }

    /** Si se registra un producto en un local donde no figuraba, queda asociado. */
    public static function asegurar(int $productoId, int $localId): void
    {
        self::execute(
            'INSERT INTO producto_local (producto_id, local_id, activo) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE activo = 1',
            [$productoId, $localId]
        );
    }

    public static function ubicacion(int $productoId, int $localId): ?array
    {
        return self::fetch(
            'SELECT stock_habitual, ubicacion_gondola FROM producto_local WHERE producto_id = ? AND local_id = ?',
            [$productoId, $localId]
        );
    }

    /**
     * Deja activos solo los locales recibidos; los demás se desactivan (no se borran,
     * así se conservan sus datos si se vuelven a marcar).
     *
     * @param array<int, array{stock_habitual: ?int, ubicacion_gondola: ?string}> $locales
     */
    public static function sincronizar(int $productoId, array $locales): void
    {
        foreach ($locales as $localId => $d) {
            self::execute(
                'INSERT INTO producto_local (producto_id, local_id, activo, stock_habitual, ubicacion_gondola)
                 VALUES (?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE activo = 1, stock_habitual = VALUES(stock_habitual), ubicacion_gondola = VALUES(ubicacion_gondola)',
                [$productoId, $localId, $d['stock_habitual'], $d['ubicacion_gondola']]
            );
        }

        $ids = array_keys($locales);
        $sql = 'UPDATE producto_local SET activo = 0 WHERE producto_id = ?';
        if ($ids !== []) {
            $sql .= ' AND local_id NOT IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
        }
        self::execute($sql, [$productoId, ...$ids]);
    }
}
