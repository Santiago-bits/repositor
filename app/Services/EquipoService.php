<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Supervisión: qué hizo cada repositor y cuándo entró cada uno (vendedores incluidos). */
final class EquipoService
{
    /** Cuenta lo hecho por cada usuario desde $desde (visitas, locales, faltantes, vencimientos y fotos). */
    private const ACTIVIDAD = "
        (SELECT COUNT(*) FROM relevamientos r WHERE r.user_id = u.id AND r.estado <> 'cancelado' AND r.fecha >= :d1) AS visitas,
        (SELECT COUNT(DISTINCT r.local_id) FROM relevamientos r WHERE r.user_id = u.id AND r.estado <> 'cancelado' AND r.fecha >= :d2) AS locales,
        (SELECT COUNT(*) FROM relevamiento_productos rp JOIN relevamientos r ON r.id = rp.relevamiento_id
         WHERE r.user_id = u.id AND r.estado <> 'cancelado' AND r.fecha >= :d3 AND rp.estado_stock IN ('sin_stock', 'bajo')) AS faltantes,
        (SELECT COUNT(*) FROM vencimientos v JOIN relevamiento_productos rp ON rp.id = v.relevamiento_producto_id
         JOIN relevamientos r ON r.id = rp.relevamiento_id
         WHERE r.user_id = u.id AND r.estado <> 'cancelado' AND r.fecha >= :d4) AS vencimientos,
        (SELECT COUNT(*) FROM fotos f JOIN relevamientos r ON r.id = f.relevamiento_id
         WHERE r.user_id = u.id AND f.deleted_at IS NULL AND r.fecha >= :d5) AS fotos,
        (SELECT MAX(r.fecha) FROM relevamientos r WHERE r.user_id = u.id AND r.estado <> 'cancelado') AS ultima_visita";

    /** Todos los usuarios activos con lo que hicieron en los últimos $dias días. Los que trabajan en la calle primero. */
    public static function miembros(int $dias = 7): array
    {
        return self::consultar(
            "SELECT u.id, u.nombre, u.apellido, u.foto_path, u.email, u.last_seen_at, r.slug AS role_slug, r.nombre AS role_nombre, " . self::ACTIVIDAD . "
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.activo = 1 AND u.deleted_at IS NULL
             ORDER BY FIELD(r.slug, 'repositor', 'vendedor', 'admin'), u.last_seen_at IS NULL, u.last_seen_at DESC, u.nombre",
            self::desde($dias)
        );
    }

    public static function miembro(int $id, int $dias): ?array
    {
        return self::consultar(
            "SELECT u.id, u.nombre, u.apellido, u.foto_path, u.email, u.activo, u.last_seen_at, u.last_login_at, r.slug AS role_slug, r.nombre AS role_nombre, " . self::ACTIVIDAD . "
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id AND u.deleted_at IS NULL",
            self::desde($dias) + ['id' => $id]
        )[0] ?? null;
    }

    private static function desde(int $dias): array
    {
        $fecha = date('Y-m-d', strtotime('-' . max(0, $dias - 1) . ' days'));
        return ['d1' => $fecha, 'd2' => $fecha, 'd3' => $fecha, 'd4' => $fecha, 'd5' => $fecha];
    }

    private static function consultar(string $sql, array $params): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
