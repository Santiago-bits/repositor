<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\RelevamientoProducto;

/** Lo que necesita el vendedor: qué falta en cada local según la última visita del repositor. */
final class VendedorService
{
    /** Días hacia atrás que se miran: una visita más vieja ya no dice nada del local. */
    private const DIAS = 30;

    /**
     * Un bloque por local con faltantes en su última visita (si después se repuso, ya no aparece).
     * @return list<array{local: string, fecha: string, repositor: string, categorias: array<string, list<array>>, sin_stock: int, bajo: int}>
     */
    public static function faltantesPorLocal(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT r.id, r.fecha, l.nombre AS local, CONCAT(u.nombre, ' ', u.apellido) AS repositor
             FROM relevamientos r
             JOIN locales l ON l.id = r.local_id AND l.activo = 1 AND l.deleted_at IS NULL
             JOIN users u ON u.id = r.user_id
             WHERE r.id IN (
                 SELECT MAX(r2.id) FROM relevamientos r2
                 WHERE r2.estado <> 'cancelado' AND r2.fecha >= CURDATE() - INTERVAL " . self::DIAS . " DAY
                 GROUP BY r2.local_id
             )
             ORDER BY r.fecha DESC, l.nombre"
        );
        $stmt->execute();

        $bloques = [];
        foreach ($stmt->fetchAll() as $v) {
            $categorias = [];
            $sinStock = 0;
            foreach (RelevamientoProducto::faltantesDeVisita((int) $v['id']) as $p) {
                $categorias[$p['categoria']][] = $p;
                $sinStock += $p['estado_stock'] === 'sin_stock' ? 1 : 0;
            }
            if ($categorias === []) {
                continue;
            }
            $total = array_sum(array_map('count', $categorias));
            $bloques[] = $v + ['categorias' => $categorias, 'sin_stock' => $sinStock, 'bajo' => $total - $sinStock];
        }
        return $bloques;
    }
}
