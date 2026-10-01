<?php
declare(strict_types=1);

namespace App\Models;

/** Lotes con fecha de vencimiento de un producto en una visita. */
final class Vencimiento extends Model
{
    public static function crear(int $relevamientoProductoId, string $fecha, ?int $cantidad, ?string $nota = null): int
    {
        return self::insert(
            'INSERT INTO vencimientos (relevamiento_producto_id, fecha_vencimiento, cantidad, nota) VALUES (?, ?, ?, ?)',
            [$relevamientoProductoId, $fecha, $cantidad, $nota]
        );
    }

    /** Todos los vencimientos cargados en la visita, con el producto. */
    public static function deVisita(int $relevamientoId): array
    {
        return self::fetchAll(
            'SELECT v.id, v.fecha_vencimiento, v.cantidad, v.nota, rp.producto_id, p.nombre, p.marca, p.presentacion
             FROM vencimientos v
             JOIN relevamiento_productos rp ON rp.id = v.relevamiento_producto_id
             JOIN productos p ON p.id = rp.producto_id
             WHERE rp.relevamiento_id = ?
             ORDER BY v.fecha_vencimiento, p.nombre, v.id',
            [$relevamientoId]
        );
    }

    public static function deRegistro(int $relevamientoProductoId): array
    {
        return self::fetchAll(
            'SELECT id, fecha_vencimiento, cantidad, nota FROM vencimientos
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
