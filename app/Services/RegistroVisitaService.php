<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ProductoLocal;
use App\Models\RelevamientoProducto;
use App\Models\Vencimiento;

/** Lo que se registra de cada producto durante una visita. */
final class RegistroVisitaService
{
    public static function guardarStock(array $visita, int $productoId, ?int $stock, ?string $estado, bool $problema, ?int $promocionId = null): void
    {
        Database::transaction(function () use ($visita, $productoId, $stock, $estado, $problema, $promocionId): void {
            RelevamientoProducto::guardarStock((int) $visita['id'], $productoId, $stock, $estado, $problema, $promocionId);
            ProductoLocal::asegurar($productoId, (int) $visita['local_id']);
        });
    }

    /** Estado por defecto si no se eligió: 0 = sin stock, cualquier otro número = normal. */
    public static function estadoPorDefecto(?int $stock): ?string
    {
        return match (true) {
            $stock === null => null,
            $stock === 0    => 'sin_stock',
            default         => 'normal',
        };
    }

    public static function agregarVencimiento(array $visita, int $productoId, string $fecha, ?int $cantidad, ?string $nota = null): int
    {
        return Database::transaction(function () use ($visita, $productoId, $fecha, $cantidad, $nota): int {
            $rpId = RelevamientoProducto::asegurar((int) $visita['id'], $productoId);
            ProductoLocal::asegurar($productoId, (int) $visita['local_id']);
            Vencimiento::crear($rpId, $fecha, $cantidad, $nota);
            return $rpId;
        });
    }

    /** Lotes todavía vigentes que se cargaron la vez anterior en este local. */
    public static function lotesAnteriores(array $visita, int $productoId): array
    {
        $anterior = RelevamientoProducto::anteriorEnLocal((int) $visita['local_id'], $productoId, (int) $visita['id']);
        if ($anterior === null) {
            return [];
        }
        $hoy = date('Y-m-d');
        return array_values(array_filter(
            Vencimiento::deRegistro((int) $anterior['id']),
            fn ($v) => $v['fecha_vencimiento'] >= $hoy && $v['retirado_at'] === null
        ));
    }

    /** Copia los lotes vigentes de la visita anterior (sin duplicar fechas ya cargadas). Devuelve cuántos copió. */
    public static function copiarLotesAnteriores(array $visita, int $productoId): int
    {
        $lotes = self::lotesAnteriores($visita, $productoId);
        if ($lotes === []) {
            return 0;
        }

        return Database::transaction(function () use ($visita, $productoId, $lotes): int {
            $rpId = RelevamientoProducto::asegurar((int) $visita['id'], $productoId);
            $existentes = array_column(Vencimiento::deRegistro($rpId), 'fecha_vencimiento');
            $copiados = 0;
            foreach ($lotes as $lote) {
                if (!in_array($lote['fecha_vencimiento'], $existentes, true)) {
                    Vencimiento::crear($rpId, $lote['fecha_vencimiento'], $lote['cantidad'] !== null ? (int) $lote['cantidad'] : null, $lote['nota'] ?? null);
                    $copiados++;
                }
            }
            return $copiados;
        });
    }
}
