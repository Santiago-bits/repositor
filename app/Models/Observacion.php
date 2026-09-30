<?php
declare(strict_types=1);

namespace App\Models;

/** Observaciones de una visita: generales (sin producto) o de un producto. */
final class Observacion extends Model
{
    /** Frases frecuentes: un toque en vez de escribir. */
    public const RAPIDAS = [
        'Producto ubicado en depósito.',
        'Quedan pocas unidades.',
        'El precio de góndola no coincide.',
        'No estaba exhibido.',
        'Producto en mal estado.',
    ];

    public static function crear(int $relevamientoId, ?int $productoId, string $texto): int
    {
        return self::insert(
            'INSERT INTO observaciones (relevamiento_id, producto_id, texto) VALUES (?, ?, ?)',
            [$relevamientoId, $productoId, $texto]
        );
    }

    public static function find(int $id): ?array
    {
        return self::fetch(
            'SELECT o.id, o.relevamiento_id, o.producto_id, r.user_id, r.estado
             FROM observaciones o
             JOIN relevamientos r ON r.id = o.relevamiento_id
             WHERE o.id = ?',
            [$id]
        );
    }

    /** Observaciones de la visita; con $productoId, solo las de ese producto. */
    public static function deVisita(int $relevamientoId, ?int $productoId = null): array
    {
        $sql = 'SELECT o.id, o.producto_id, o.texto, o.created_at, p.nombre AS producto, p.presentacion
                FROM observaciones o
                LEFT JOIN productos p ON p.id = o.producto_id
                WHERE o.relevamiento_id = ?';
        $params = [$relevamientoId];
        if ($productoId !== null) {
            $sql .= ' AND o.producto_id = ?';
            $params[] = $productoId;
        }
        return self::fetchAll($sql . ' ORDER BY o.created_at DESC, o.id DESC', $params);
    }

    public static function eliminar(int $id): void
    {
        self::execute('DELETE FROM observaciones WHERE id = ?', [$id]);
    }
}
