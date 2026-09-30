<?php
declare(strict_types=1);

namespace App\Models;

/** Fotos de una visita. El archivo está en storage/; acá solo la ruta y los metadatos. */
final class Foto extends Model
{
    public static function crear(int $relevamientoId, ?int $productoId, array $info): int
    {
        return self::insert(
            'INSERT INTO fotos (relevamiento_id, producto_id, path, mime, bytes, ancho, alto) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$relevamientoId, $productoId, $info['path'], $info['mime'], $info['bytes'], $info['ancho'], $info['alto']]
        );
    }

    /** Con datos de la visita, para controlar permisos. */
    public static function find(int $id): ?array
    {
        return self::fetch(
            'SELECT f.id, f.relevamiento_id, f.producto_id, f.path, r.user_id, r.estado
             FROM fotos f
             JOIN relevamientos r ON r.id = f.relevamiento_id
             WHERE f.id = ? AND f.deleted_at IS NULL',
            [$id]
        );
    }

    /** Fotos de la visita; con $productoId, solo las de ese producto. */
    public static function deVisita(int $relevamientoId, ?int $productoId = null): array
    {
        $sql = 'SELECT f.id, f.producto_id, f.created_at, p.nombre AS producto, p.presentacion
                FROM fotos f
                LEFT JOIN productos p ON p.id = f.producto_id
                WHERE f.relevamiento_id = ? AND f.deleted_at IS NULL';
        $params = [$relevamientoId];
        if ($productoId !== null) {
            $sql .= ' AND f.producto_id = ?';
            $params[] = $productoId;
        }
        return self::fetchAll($sql . ' ORDER BY f.created_at DESC, f.id DESC', $params);
    }

    /** Para la galería del admin. @param array{desde: string, hasta: string, local_id: ?int} $f */
    public static function buscar(array $f, int $limite = 300): array
    {
        $sql = "SELECT f.id, f.relevamiento_id, f.created_at, r.fecha, l.nombre AS local, p.nombre AS producto, p.presentacion,
                       CONCAT(u.nombre, ' ', u.apellido) AS usuario
                FROM fotos f
                JOIN relevamientos r ON r.id = f.relevamiento_id
                JOIN locales l ON l.id = r.local_id
                JOIN users u ON u.id = r.user_id
                LEFT JOIN productos p ON p.id = f.producto_id
                WHERE f.deleted_at IS NULL AND r.fecha BETWEEN ? AND ?";
        $params = [$f['desde'], $f['hasta']];
        if (!empty($f['local_id'])) {
            $sql .= ' AND r.local_id = ?';
            $params[] = $f['local_id'];
        }
        return self::fetchAll($sql . ' ORDER BY f.created_at DESC LIMIT ' . max(1, $limite), $params);
    }

    public static function eliminar(int $id): void
    {
        self::execute('UPDATE fotos SET deleted_at = NOW() WHERE id = ?', [$id]);
    }
}
