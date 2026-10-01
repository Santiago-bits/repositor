<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Configuracion;
use App\Models\Local;
use App\Models\Vencimiento;

/** Datos del Resumen: cómo viene la semana, qué falta y qué locales hace mucho que no se visitan. */
final class DashboardService
{
    public static function tarjetas(array $user): array
    {
        $dias = (int) Configuracion::get('vencimiento_dias_proximo', '15');

        return [
            ['label' => 'Visitas de hoy', 'icon' => 'bi-geo-alt', 'tone' => 'ok', 'href' => '/historial',
             'value' => self::count("SELECT COUNT(*) FROM relevamientos WHERE fecha = CURDATE() AND estado <> 'cancelado'")],
            ['label' => 'Sin stock hoy', 'icon' => 'bi-x-octagon', 'tone' => 'danger', 'href' => '/mensaje',
             'value' => self::count("SELECT COUNT(*) FROM relevamiento_productos rp
                                     JOIN relevamientos r ON r.id = rp.relevamiento_id
                                     WHERE r.fecha = CURDATE() AND r.estado <> 'cancelado' AND rp.estado_stock = 'sin_stock'")],
            // Las mismas que se ven en Inicio (sin las retiradas).
            ['label' => "Fechas cortas ({$dias} días)", 'icon' => 'bi-calendar-x', 'tone' => 'warn', 'href' => '/#fechas-cortas',
             'value' => count(Vencimiento::proximos($user, $dias))],
            // Lo que importa de las promos: cuánto quedó cuando terminan (se cuenta el lunes).
            ['label' => 'Promos para contar', 'icon' => 'bi-123', 'tone' => 'warn', 'href' => '/#locales',
             'value' => array_sum(array_map(
                 fn ($l) => ConteoService::pendientes((int) $l['id']),
                 Local::paraUsuario($user)
             ))],
            ['label' => 'Locales', 'icon' => 'bi-shop', 'tone' => '', 'href' => '/admin/locales',
             'value' => self::count('SELECT COUNT(*) FROM locales WHERE activo = 1 AND deleted_at IS NULL')],
            ['label' => 'Productos', 'icon' => 'bi-box-seam', 'tone' => '', 'href' => '/admin/productos',
             'value' => self::count('SELECT COUNT(*) FROM productos WHERE activo = 1 AND deleted_at IS NULL')],
        ];
    }

    /** Últimos 7 días: totales y visitas por día (para las barritas). */
    public static function semana(): array
    {
        $totales = self::lista(
            "SELECT COUNT(DISTINCT r.id) AS visitas, COUNT(DISTINCT r.local_id) AS locales,
                    (SELECT COUNT(*) FROM relevamiento_productos rp JOIN relevamientos r2 ON r2.id = rp.relevamiento_id
                     WHERE r2.fecha >= CURDATE() - INTERVAL 6 DAY AND r2.estado <> 'cancelado') AS productos,
                    (SELECT COUNT(*) FROM fotos f JOIN relevamientos r3 ON r3.id = f.relevamiento_id
                     WHERE r3.fecha >= CURDATE() - INTERVAL 6 DAY AND f.deleted_at IS NULL) AS fotos
             FROM relevamientos r
             WHERE r.fecha >= CURDATE() - INTERVAL 6 DAY AND r.estado <> 'cancelado'"
        )[0];

        $porFecha = [];
        foreach (self::lista(
            "SELECT fecha, COUNT(*) AS visitas FROM relevamientos
             WHERE fecha >= CURDATE() - INTERVAL 6 DAY AND estado <> 'cancelado'
             GROUP BY fecha"
        ) as $f) {
            $porFecha[$f['fecha']] = (int) $f['visitas'];
        }

        $dias = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-{$i} days"));
            $dias[] = ['fecha' => $fecha, 'visitas' => $porFecha[$fecha] ?? 0];
        }

        return ['totales' => $totales, 'dias' => $dias, 'max' => max(1, ...array_column($dias, 'visitas'))];
    }

    /** Locales activos que hace más de $dias días que no se visitan (o nunca). Los más olvidados primero. */
    public static function localesOlvidados(int $dias = 7, int $limite = 5): array
    {
        return self::lista(
            "SELECT l.id, l.nombre, l.tipo, MAX(r.fecha) AS ultima
             FROM locales l
             LEFT JOIN relevamientos r ON r.local_id = l.id AND r.estado <> 'cancelado'
             WHERE l.activo = 1 AND l.deleted_at IS NULL
             GROUP BY l.id, l.nombre, l.tipo
             HAVING ultima IS NULL OR ultima < CURDATE() - INTERVAL ? DAY
             ORDER BY MAX(r.fecha) IS NOT NULL, MAX(r.fecha), l.nombre
             LIMIT " . max(1, $limite),
            [$dias]
        );
    }

    public static function actividadReciente(int $limite = 6): array
    {
        return self::lista(
            "SELECT r.id, r.fecha, l.nombre AS local, l.tipo,
                    (SELECT COUNT(*) FROM relevamiento_productos rp WHERE rp.relevamiento_id = r.id) AS productos,
                    (SELECT COUNT(*) FROM fotos f WHERE f.relevamiento_id = r.id AND f.deleted_at IS NULL) AS fotos
             FROM relevamientos r
             JOIN locales l ON l.id = r.local_id
             WHERE r.estado <> 'cancelado'
             ORDER BY r.inicio_at DESC
             LIMIT " . max(1, $limite)
        );
    }

    /** Qué falta y dónde (últimos 30 días). */
    public static function faltantes(): array
    {
        return [
            'sin_stock' => self::lista(
                "SELECT p.id, p.nombre, p.presentacion, COUNT(*) AS veces
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
                 HAVING sin_stock > 0 OR bajo > 0
                 ORDER BY sin_stock DESC, bajo DESC, l.nombre
                 LIMIT 5"
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
