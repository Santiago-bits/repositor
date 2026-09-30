<?php
declare(strict_types=1);

namespace App\Models;

/** Datos de un producto propios de cada local (góndola, stock habitual). El catálogo es el mismo para todos. */
final class ProductoLocal extends Model
{
    /**
     * Todos los productos activos (el catálogo es el mismo en cualquier local), con lo ya
     * registrado en la visita (rp_id null = pendiente) y la góndola de ese local si se cargó.
     */
    public static function productosDeLocal(int $localId, int $relevamientoId): array
    {
        return self::fetchAll(
            'SELECT p.id, p.nombre, p.marca, p.presentacion, p.imagen_path, pl.ubicacion_gondola,
                    rp.id AS rp_id, rp.stock, rp.estado_stock
             FROM productos p
             LEFT JOIN producto_local pl ON pl.producto_id = p.id AND pl.local_id = ?
             LEFT JOIN relevamiento_productos rp ON rp.producto_id = p.id AND rp.relevamiento_id = ?
             WHERE p.activo = 1 AND p.deleted_at IS NULL
             ORDER BY p.nombre, p.presentacion',
            [$localId, $relevamientoId]
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
}
