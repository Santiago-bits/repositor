<?php
declare(strict_types=1);

namespace App\Models;

/** Lo registrado de un producto en una visita (un registro por producto y visita). */
final class RelevamientoProducto extends Model
{
    /** estado => [etiqueta, clase de badge] */
    public const ESTADOS = [
        'normal'      => ['Normal', 'text-bg-success'],
        'bajo'        => ['Bajo stock', 'text-bg-warning'],
        'sin_stock'   => ['Sin stock', 'text-bg-danger'],
        'no_exhibido' => ['No exhibido', 'text-bg-secondary'],
    ];

    public static function find(int $relevamientoId, int $productoId): ?array
    {
        return self::fetch(
            'SELECT id, stock, estado_stock, con_problema, updated_at, created_at
             FROM relevamiento_productos WHERE relevamiento_id = ? AND producto_id = ?',
            [$relevamientoId, $productoId]
        );
    }

    /** $promocionId: si es un conteo de promoción (si no, se conserva la que ya tuviera). */
    public static function guardarStock(int $relevamientoId, int $productoId, ?int $stock, ?string $estado, bool $problema, ?int $promocionId = null): void
    {
        self::execute(
            'INSERT INTO relevamiento_productos (relevamiento_id, producto_id, stock, estado_stock, con_problema, promocion_id)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE stock = VALUES(stock), estado_stock = VALUES(estado_stock),
                                     con_problema = VALUES(con_problema), promocion_id = COALESCE(VALUES(promocion_id), promocion_id)',
            [$relevamientoId, $productoId, $stock, $estado, (int) $problema, $promocionId]
        );
    }

    /** Crea el registro vacío si no existe (para colgarle vencimientos) y devuelve su id. */
    public static function asegurar(int $relevamientoId, int $productoId): int
    {
        self::execute(
            'INSERT IGNORE INTO relevamiento_productos (relevamiento_id, producto_id) VALUES (?, ?)',
            [$relevamientoId, $productoId]
        );
        return (int) self::value(
            'SELECT id FROM relevamiento_productos WHERE relevamiento_id = ? AND producto_id = ?',
            [$relevamientoId, $productoId]
        );
    }

    /** Productos registrados en la visita, el último tocado primero. */
    public static function deVisita(int $relevamientoId): array
    {
        return self::fetchAll(
            'SELECT rp.id, rp.producto_id, rp.stock, rp.estado_stock, rp.con_problema,
                    p.nombre, p.marca, p.presentacion,
                    (SELECT COUNT(*) FROM vencimientos v WHERE v.relevamiento_producto_id = rp.id) AS vencimientos
             FROM relevamiento_productos rp
             JOIN productos p ON p.id = rp.producto_id
             WHERE rp.relevamiento_id = ?
             ORDER BY COALESCE(rp.updated_at, rp.created_at) DESC, rp.id DESC',
            [$relevamientoId]
        );
    }

    /** El registro del mismo producto en la visita anterior al mismo local (para tener referencia). */
    public static function anteriorEnLocal(int $localId, int $productoId, int $exceptoRelevamiento): ?array
    {
        return self::fetch(
            "SELECT rp.id, rp.stock, rp.estado_stock, r.fecha
             FROM relevamiento_productos rp
             JOIN relevamientos r ON r.id = rp.relevamiento_id
             WHERE r.local_id = ? AND rp.producto_id = ? AND r.id <> ? AND r.estado <> 'cancelado'
             ORDER BY r.inicio_at DESC
             LIMIT 1",
            [$localId, $productoId, $exceptoRelevamiento]
        );
    }
}
