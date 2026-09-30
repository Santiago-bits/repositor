<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\RelevamientoProducto;

/** Reporte de relevamientos: resumen en pantalla y exportación CSV (se abre con Excel). */
final class ReporteService
{
    /** @param array{desde: string, hasta: string, local_id: ?int, user_id: ?int} $f */
    public static function filas(array $f): array
    {
        [$where, $params] = self::filtro($f);

        $stmt = Database::connection()->prepare(
            "SELECT r.id AS visita, r.fecha, DATE_FORMAT(r.inicio_at, '%H:%i') AS hora,
                    CONCAT(u.nombre, ' ', u.apellido) AS usuario, l.nombre AS local,
                    p.nombre AS producto, p.presentacion, p.marca, p.codigo_barras,
                    rp.stock, rp.estado_stock, rp.con_problema, pm.fecha_inicio AS promo_desde, pm.fecha_fin AS promo_hasta,
                    (SELECT GROUP_CONCAT(CONCAT(DATE_FORMAT(v.fecha_vencimiento, '%d/%m/%Y'), ' x ', COALESCE(v.cantidad, '?'))
                            ORDER BY v.fecha_vencimiento SEPARATOR ' | ')
                       FROM vencimientos v WHERE v.relevamiento_producto_id = rp.id) AS vencimientos,
                    (SELECT GROUP_CONCAT(o.texto ORDER BY o.id SEPARATOR ' | ')
                       FROM observaciones o WHERE o.relevamiento_id = r.id AND o.producto_id = rp.producto_id) AS observaciones,
                    (SELECT COUNT(*) FROM fotos f
                      WHERE f.relevamiento_id = r.id AND f.producto_id = rp.producto_id AND f.deleted_at IS NULL) AS fotos
             FROM relevamiento_productos rp
             JOIN relevamientos r ON r.id = rp.relevamiento_id
             JOIN locales l ON l.id = r.local_id
             JOIN users u ON u.id = r.user_id
             JOIN productos p ON p.id = rp.producto_id
             LEFT JOIN promociones pm ON pm.id = rp.promocion_id
             WHERE {$where}
             ORDER BY r.fecha, r.inicio_at, l.nombre, p.nombre"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Observaciones generales de las visitas (sin producto). */
    public static function observacionesGenerales(array $f): array
    {
        [$where, $params] = self::filtro($f);
        $stmt = Database::connection()->prepare(
            "SELECT r.fecha, l.nombre AS local, CONCAT(u.nombre, ' ', u.apellido) AS usuario, o.texto
             FROM observaciones o
             JOIN relevamientos r ON r.id = o.relevamiento_id
             JOIN locales l ON l.id = r.local_id
             JOIN users u ON u.id = r.user_id
             WHERE {$where} AND o.producto_id IS NULL
             ORDER BY r.fecha, o.id"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function resumen(array $f): array
    {
        [$where, $params] = self::filtro($f);
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(DISTINCT r.id) AS visitas,
                    COUNT(DISTINCT r.local_id) AS locales,
                    COUNT(rp.id) AS productos,
                    COALESCE(SUM(rp.estado_stock = 'sin_stock'), 0) AS sin_stock,
                    COALESCE(SUM(rp.estado_stock = 'bajo'), 0) AS bajo,
                    COALESCE(SUM(rp.promocion_id IS NOT NULL), 0) AS promos
             FROM relevamientos r
             LEFT JOIN relevamiento_productos rp ON rp.relevamiento_id = r.id
             WHERE {$where}"
        );
        $stmt->execute($params);
        return array_map('intval', $stmt->fetch() ?: []);
    }

    /** Envía el CSV al navegador. Separador ";" y BOM para que Excel en español lo abra bien. */
    public static function descargarCsv(array $f): never
    {
        $nombre = 'jacob-reporte-' . str_replace('-', '', $f['desde']) . '-' . str_replace('-', '', $f['hasta']) . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Fecha', 'Hora', 'Usuario', 'Local', 'Producto', 'Presentación', 'Marca', 'Código',
            'Stock', 'Estado', 'Con problema', 'Promoción', 'Vencimientos', 'Observaciones', 'Fotos'], ';');

        foreach (self::filas($f) as $r) {
            fputcsv($out, array_map([self::class, 'celda'], [
                fecha($r['fecha']), $r['hora'], $r['usuario'], $r['local'], $r['producto'], $r['presentacion'],
                $r['marca'], $r['codigo_barras'], $r['stock'],
                $r['estado_stock'] ? RelevamientoProducto::ESTADOS[$r['estado_stock']][0] : '',
                $r['con_problema'] ? 'Sí' : '',
                $r['promo_desde'] ? fecha($r['promo_desde']) . ' al ' . fecha($r['promo_hasta']) : '',
                $r['vencimientos'], $r['observaciones'], $r['fotos'],
            ]), ';');
        }
        foreach (self::observacionesGenerales($f) as $o) {
            fputcsv($out, array_map([self::class, 'celda'], [
                fecha($o['fecha']), '', $o['usuario'], $o['local'], '(Observación general)', '', '', '', '', '', '', '', '', $o['texto'], '',
            ]), ';');
        }
        fclose($out);
        exit;
    }

    /** Evita que Excel interprete un texto como fórmula (inyección CSV). */
    private static function celda(mixed $valor): string
    {
        $texto = (string) ($valor ?? '');
        return preg_match('/^[=+\-@\t\r]/', $texto) ? "'" . $texto : $texto;
    }

    private static function filtro(array $f): array
    {
        $where = "r.estado <> 'cancelado' AND r.fecha BETWEEN ? AND ?";
        $params = [$f['desde'], $f['hasta']];
        foreach (['local_id' => 'r.local_id', 'user_id' => 'r.user_id'] as $clave => $columna) {
            if (!empty($f[$clave])) {
                $where .= " AND {$columna} = ?";
                $params[] = $f[$clave];
            }
        }
        return [$where, $params];
    }
}
