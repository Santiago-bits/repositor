<?php
declare(strict_types=1);

namespace App\Models;

/** Lotes con fecha de vencimiento de un producto en una visita. */
final class Vencimiento extends Model
{
    public static function crear(int $relevamientoProductoId, string $fecha, ?int $cantidad): int
    {
        return self::insert(
            'INSERT INTO vencimientos (relevamiento_producto_id, fecha_vencimiento, cantidad) VALUES (?, ?, ?)',
            [$relevamientoProductoId, $fecha, $cantidad]
        );
    }

    public static function deRegistro(int $relevamientoProductoId): array
    {
        return self::fetchAll(
            'SELECT id, fecha_vencimiento, cantidad FROM vencimientos
             WHERE relevamiento_producto_id = ? ORDER BY fecha_vencimiento, id',
            [$relevamientoProductoId]
        );
    }

    /** El vencimiento, solo si pertenece a esa visita. */
    public static function findEnVisita(int $id, int $relevamientoId): ?array
    {
        return self::fetch(
            'SELECT v.id, rp.id AS rp_id, rp.producto_id
             FROM vencimientos v
             JOIN relevamiento_productos rp ON rp.id = v.relevamiento_producto_id
             WHERE v.id = ? AND rp.relevamiento_id = ?',
            [$id, $relevamientoId]
        );
    }

    public static function eliminar(int $id): void
    {
        self::execute('DELETE FROM vencimientos WHERE id = ?', [$id]);
    }
}
