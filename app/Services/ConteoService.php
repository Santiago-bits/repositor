<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Configuracion;

/**
 * Conteo de promociones: al terminar una promo (o hasta N días después, configurable)
 * se cuenta cuánto stock quedó de cada producto promocionado en el local.
 */
final class ConteoService
{
    /**
     * Promos para contar en la visita: las que terminan en el período y todavía no se contaron
     * en ese local, más las que ya se contaron en esta misma visita (para mostrarlas con ✅).
     */
    public static function items(array $visita): array
    {
        return self::consultar((int) $visita['local_id'], $visita['fecha'], (int) $visita['id']);
    }

    /** Cuántas promos faltan contar en un local (sin importar la visita). */
    public static function pendientes(int $localId, ?string $fecha = null): int
    {
        return count(self::consultar($localId, $fecha ?? date('Y-m-d'), 0));
    }

    public static function guardar(array $visita, array $item, int $stock, bool $noExhibido): void
    {
        $estado = $noExhibido ? 'no_exhibido' : RegistroVisitaService::estadoPorDefecto($stock);
        RegistroVisitaService::guardarStock($visita, (int) $item['producto_id'], $stock, $estado, false, (int) $item['promo_id']);

    }

    private static function consultar(int $localId, string $fecha, int $relevamientoId): array
    {
        $gracia = max(0, (int) Configuracion::get('conteo_promos_dias_gracia', '2'));

        $stmt = Database::connection()->prepare(
            "SELECT p.id AS promo_id, p.producto_id, p.fecha_inicio, p.fecha_fin, p.precio_promo,
                    pr.nombre, pr.presentacion, pr.marca, pr.imagen_path,
                    rp.id AS rp_id, rp.stock, rp.estado_stock
             FROM promociones p
             JOIN promocion_local pl ON pl.promocion_id = p.id AND pl.local_id = ?
             JOIN productos pr ON pr.id = p.producto_id
             LEFT JOIN relevamiento_productos rp ON rp.promocion_id = p.id AND rp.relevamiento_id = ?
             WHERE p.deleted_at IS NULL AND p.estado <> 'cancelada'
               AND p.fecha_fin BETWEEN ? - INTERVAL ? DAY AND ?
               AND (rp.id IS NOT NULL OR NOT EXISTS (
                    SELECT 1 FROM relevamiento_productos x
                    JOIN relevamientos r ON r.id = x.relevamiento_id
                    WHERE x.promocion_id = p.id AND r.local_id = ? AND r.estado <> 'cancelado' AND r.fecha >= p.fecha_fin))
             ORDER BY pr.nombre, pr.presentacion"
        );
        $stmt->execute([$localId, $relevamientoId, $fecha, $gracia, $fecha, $localId]);
        return $stmt->fetchAll();
    }
}
