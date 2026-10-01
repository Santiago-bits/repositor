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

    /**
     * Fechas cortas: lotes que vencen hasta dentro de $dias (y los vencidos hace poco), en los locales del usuario.
     * De cada producto en cada local se usa solo el último relevamiento: los lotes viejos ya se habrán vendido.
     */
    public static function proximos(array $user, int $dias, int $vencidosHace = 7): array
    {
        $esAdmin = $user['role_slug'] === 'admin';
        $params = $esAdmin ? [] : [(int) $user['id']];
        $params[] = date('Y-m-d', strtotime("-{$vencidosHace} days"));
        $params[] = date('Y-m-d', strtotime("+{$dias} days"));

        return self::fetchAll(
            'SELECT * FROM (
                SELECT v.id, v.fecha_vencimiento, v.cantidad, v.nota, r.local_id, l.nombre AS local,
                       p.id AS producto_id, p.nombre, p.marca, p.presentacion,
                       DENSE_RANK() OVER (PARTITION BY r.local_id, rp.producto_id ORDER BY r.fecha DESC, r.id DESC) AS ultimo
                FROM vencimientos v
                JOIN relevamiento_productos rp ON rp.id = v.relevamiento_producto_id
                JOIN relevamientos r ON r.id = rp.relevamiento_id AND r.estado <> \'cancelado\'
                JOIN locales l ON l.id = r.local_id AND l.activo = 1 AND l.deleted_at IS NULL
                ' . ($esAdmin ? '' : 'JOIN local_user lu ON lu.local_id = l.id AND lu.user_id = ?') . '
                JOIN productos p ON p.id = rp.producto_id AND p.deleted_at IS NULL
             ) t
             WHERE t.ultimo = 1 AND t.fecha_vencimiento BETWEEN ? AND ?
             ORDER BY t.fecha_vencimiento, t.local, t.nombre
             LIMIT 100',
            $params
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
