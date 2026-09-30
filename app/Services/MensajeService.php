<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Observacion;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Vencimiento;

/**
 * Arma el mensaje para el supervisor a partir de lo registrado.
 * $tipo 'promos' = solo el conteo de promociones; 'completo' = stock, vencimientos y observaciones.
 */
final class MensajeService
{
    /** Todas las visitas (no canceladas) del usuario en una fecha. */
    public static function delDia(int $userId, string $fecha, string $tipo): string
    {
        return self::armar(self::visitas('r.user_id = ? AND r.fecha = ?', [$userId, $fecha]), $fecha, $tipo);
    }

    public static function deVisita(array $visita, string $tipo): string
    {
        return self::armar(self::visitas('r.id = ?', [(int) $visita['id']]), $visita['fecha'], $tipo);
    }

    private static function visitas(string $where, array $params): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT r.id, r.estado, r.local_id, l.nombre AS local
             FROM relevamientos r JOIN locales l ON l.id = r.local_id
             WHERE {$where} AND r.estado <> 'cancelado'
             ORDER BY r.inicio_at"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private static function armar(array $visitas, string $fecha, string $tipo): string
    {
        $soloPromos = $tipo === 'promos';
        $dia = dia_semana(strtotime($fecha)) . ' ' . date('d/m', strtotime($fecha));
        $bloques = [];

        foreach ($visitas as $v) {
            $lineas = [];
            $vencimientos = [];

            foreach (self::productos((int) $v['id']) as $p) {
                if ($soloPromos && $p['promocion_id'] === null) {
                    continue;
                }
                $lineas[] = '- ' . Producto::nombreCompleto($p) . ': ' . self::stockTexto($p) . ($p['con_problema'] ? ' ⚠️ con problema' : '');

                if (!$soloPromos) {
                    foreach (Vencimiento::deRegistro((int) $p['rp_id']) as $venc) {
                        $estado = VencimientoService::estado($venc['fecha_vencimiento']);
                        if ($estado['clave'] !== 'ok') {
                            $cant = $venc['cantidad'] !== null ? (int) $venc['cantidad'] . ' u. ' : '';
                            $vencimientos[] = '- ' . Producto::nombreCompleto($p) . ": {$cant}"
                                . ($estado['clave'] === 'vencido' ? 'vencidas el ' : 'vencen el ') . fecha($venc['fecha_vencimiento'], 'd/m');
                        }
                    }
                }
            }

            // Promos marcadas en el local que no tienen stock anotado: igual van en el mensaje.
            if ($soloPromos) {
                $conStock = array_map('intval', array_column(
                    array_filter(self::productos((int) $v['id']), fn ($p) => $p['promocion_id'] !== null),
                    'producto_id'
                ));
                foreach (Promocion::vigentesEnLocal((int) $v['local_id'], $fecha) as $pid => $promo) {
                    if (!in_array((int) $pid, $conStock, true)) {
                        $lineas[] = '- ' . Producto::nombreCompleto($promo) . ': en promo';
                    }
                }
            }

            $observaciones = [];
            if (!$soloPromos) {
                foreach (array_reverse(Observacion::deVisita((int) $v['id'])) as $o) {
                    $observaciones[] = '- ' . $o['texto'] . ($o['producto'] ? ' (' . trim($o['producto'] . ' ' . $o['presentacion']) . ')' : '');
                }
            }

            if ($lineas === [] && $observaciones === []) {
                continue;
            }

            $bloque = $v['local'];
            if ($lineas !== []) {
                $bloque .= "\n" . implode("\n", $lineas);
            }
            if ($vencimientos !== []) {
                $bloque .= "\nVencimientos próximos:\n" . implode("\n", $vencimientos);
            }
            if ($observaciones !== []) {
                $bloque .= "\nObservaciones:\n" . implode("\n", $observaciones);
            }
            $bloques[] = $bloque;
        }

        $titulo = ($soloPromos ? 'Relevamiento de promociones' : 'Relevamiento') . " — {$dia}";
        if ($bloques === []) {
            return $titulo . "\n\nNo hay datos registrados" . ($soloPromos ? ' de promociones' : '') . ' para esta fecha.';
        }

        return $titulo . "\n\nLocales relevados:\n\n" . implode("\n\n", $bloques);
    }

    private static function productos(int $relevamientoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT rp.id AS rp_id, rp.producto_id, rp.stock, rp.estado_stock, rp.con_problema, rp.promocion_id, p.nombre, p.presentacion
             FROM relevamiento_productos rp JOIN productos p ON p.id = rp.producto_id
             WHERE rp.relevamiento_id = ?
             ORDER BY p.nombre, p.presentacion'
        );
        $stmt->execute([$relevamientoId]);
        return $stmt->fetchAll();
    }

    private static function stockTexto(array $p): string
    {
        if ($p['estado_stock'] === 'no_exhibido') {
            return match (true) {
                $p['stock'] === null      => 'no exhibido',
                (int) $p['stock'] === 0   => 'no exhibido (sin stock)',
                default                   => (int) $p['stock'] . ' unidades (no exhibido)',
            };
        }
        if ($p['stock'] === null) {
            return 'sin dato';
        }
        $n = (int) $p['stock'];
        if ($n === 0) {
            return 'sin stock';
        }
        return $n . ($n === 1 ? ' unidad' : ' unidades') . ($p['estado_stock'] === 'bajo' ? ' (bajo stock)' : '');
    }
}
