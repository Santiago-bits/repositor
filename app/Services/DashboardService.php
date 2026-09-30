<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Configuracion;

final class DashboardService
{
    public static function tarjetas(): array
    {
        $dias = (int) Configuracion::get('vencimiento_dias_proximo', '15');

        return [
            ['label' => 'Locales activos', 'icon' => 'bi-shop', 'tone' => '', 'href' => '/admin/locales',
             'value' => self::count('SELECT COUNT(*) FROM locales WHERE activo = 1 AND deleted_at IS NULL')],
            ['label' => 'Productos registrados', 'icon' => 'bi-box-seam', 'tone' => '', 'href' => '/admin/productos',
             'value' => self::count('SELECT COUNT(*) FROM productos WHERE activo = 1 AND deleted_at IS NULL')],
            ['label' => 'Promociones activas', 'icon' => 'bi-megaphone', 'tone' => '', 'href' => '/admin/promociones',
             'value' => self::count("SELECT COUNT(*) FROM promociones
                                     WHERE estado = 'activa' AND deleted_at IS NULL
                                       AND CURDATE() BETWEEN fecha_inicio AND fecha_fin")],
            ['label' => 'Relevamientos de hoy', 'icon' => 'bi-clipboard-check', 'tone' => 'ok', 'href' => null,
             'value' => self::count("SELECT COUNT(*) FROM relevamientos WHERE fecha = CURDATE() AND estado <> 'cancelado'")],
            ['label' => 'Sin stock (hoy)', 'icon' => 'bi-x-octagon', 'tone' => 'danger', 'href' => null,
             'value' => self::count("SELECT COUNT(*) FROM relevamiento_productos rp
                                     JOIN relevamientos r ON r.id = rp.relevamiento_id
                                     WHERE r.fecha = CURDATE() AND rp.estado_stock = 'sin_stock'")],
            ['label' => "Vencen en {$dias} días", 'icon' => 'bi-calendar-x', 'tone' => 'warn', 'href' => null,
             'value' => self::count(
                 'SELECT COUNT(DISTINCT rp.producto_id, r.local_id, v.fecha_vencimiento)
                  FROM vencimientos v
                  JOIN relevamiento_productos rp ON rp.id = v.relevamiento_producto_id
                  JOIN relevamientos r ON r.id = rp.relevamiento_id
                  WHERE v.fecha_vencimiento BETWEEN CURDATE() AND CURDATE() + INTERVAL ? DAY',
                 [$dias]
             )],
        ];
    }

    public static function actividadReciente(int $limite = 8): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT r.id, r.fecha, r.inicio_at, r.estado, l.nombre AS local, l.tipo,
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario,
                    (SELECT COUNT(*) FROM relevamiento_productos rp WHERE rp.relevamiento_id = r.id) AS productos
             FROM relevamientos r
             JOIN locales l ON l.id = r.local_id
             JOIN users u ON u.id = r.user_id
             ORDER BY r.inicio_at DESC
             LIMIT " . max(1, $limite)
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Listas para decidir rápido: qué falta, dónde y qué está por vencer. */
    public static function estadisticas(): array
    {
        $dias = (int) Configuracion::get('vencimiento_dias_proximo', '15');

        return [
            'sin_stock' => self::lista(
                "SELECT p.nombre, p.presentacion, COUNT(*) AS veces
                 FROM relevamiento_productos rp
                 JOIN relevamientos r ON r.id = rp.relevamiento_id
                 JOIN productos p ON p.id = rp.producto_id
                 WHERE rp.estado_stock = 'sin_stock' AND r.estado <> 'cancelado' AND r.fecha >= CURDATE() - INTERVAL 30 DAY
                 GROUP BY p.id, p.nombre, p.presentacion
                 ORDER BY veces DESC, p.nombre
                 LIMIT 5"
            ),
            'locales' => self::lista(
                "SELECT l.nombre, COUNT(DISTINCT r.id) AS visitas,
                        COALESCE(SUM(rp.estado_stock = 'sin_stock'), 0) AS sin_stock,
                        COALESCE(SUM(rp.estado_stock = 'bajo'), 0) AS bajo
                 FROM relevamientos r
                 JOIN locales l ON l.id = r.local_id
                 LEFT JOIN relevamiento_productos rp ON rp.relevamiento_id = r.id
                 WHERE r.estado <> 'cancelado' AND r.fecha >= CURDATE() - INTERVAL 30 DAY
                 GROUP BY l.id, l.nombre
                 ORDER BY sin_stock DESC, bajo DESC, l.nombre
                 LIMIT 5"
            ),
            // Solo los lotes del último registro de cada producto en cada local (no repetir visitas viejas).
            'vencimientos' => self::lista(
                "SELECT v.fecha_vencimiento, v.cantidad, p.nombre, p.presentacion, l.nombre AS local
                 FROM vencimientos v
                 JOIN relevamiento_productos rp ON rp.id = v.relevamiento_producto_id
                 JOIN relevamientos r ON r.id = rp.relevamiento_id
                 JOIN productos p ON p.id = rp.producto_id
                 JOIN locales l ON l.id = r.local_id
                 WHERE v.fecha_vencimiento BETWEEN CURDATE() AND CURDATE() + INTERVAL ? DAY
                   AND rp.id = (SELECT rp2.id FROM relevamiento_productos rp2
                                JOIN relevamientos r2 ON r2.id = rp2.relevamiento_id
                                WHERE rp2.producto_id = rp.producto_id AND r2.local_id = r.local_id AND r2.estado <> 'cancelado'
                                  AND EXISTS (SELECT 1 FROM vencimientos v2 WHERE v2.relevamiento_producto_id = rp2.id)
                                ORDER BY r2.inicio_at DESC LIMIT 1)
                 ORDER BY v.fecha_vencimiento, l.nombre
                 LIMIT 10",
                [$dias]
            ),
        ];
    }

    private static function lista(string $sql, array $params = []): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private static function count(string $sql, array $params = []): int
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
