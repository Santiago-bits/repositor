<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Una fecha anotada: un producto (del catálogo o escrito a mano) que vence en un local.
 * Cada fecha es de quien la anotó; el admin ve todas.
 */
final class Fecha extends Model
{
    private const SELECT = "SELECT f.id, f.user_id, f.local_id, f.producto_id, f.producto_texto, f.fecha_vencimiento,
                                   f.cantidad, f.nota, f.retirado_at, f.created_at,
                                   l.nombre AS local, p.nombre, p.presentacion, p.marca, p.imagen_path,
                                   CONCAT(u.nombre, ' ', u.apellido) AS usuario
                            FROM fechas f
                            JOIN locales l ON l.id = f.local_id AND l.deleted_at IS NULL
                            JOIN users u ON u.id = f.user_id
                            LEFT JOIN productos p ON p.id = f.producto_id";

    public static function crear(array $d): int
    {
        return self::insert(
            'INSERT INTO fechas (user_id, local_id, producto_id, producto_texto, fecha_vencimiento, cantidad, nota)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$d['user_id'], $d['local_id'], $d['producto_id'], $d['producto_texto'], $d['fecha_vencimiento'], $d['cantidad'], $d['nota']]
        );
    }

    public static function find(int $id): ?array
    {
        return self::fetch(self::SELECT . ' WHERE f.id = ?', [$id]);
    }

    /**
     * Fechas sin retirar (las vencidas hace más de $vencidosHace días ya no se muestran).
     * $userId null = de todos (admin). Las más próximas primero.
     */
    public static function pendientes(?int $userId, ?int $localId = null, int $vencidosHace = 30): array
    {
        $sql = self::SELECT . ' WHERE f.retirado_at IS NULL AND f.fecha_vencimiento >= ?';
        $params = [date('Y-m-d', strtotime("-{$vencidosHace} days"))];
        if ($userId !== null) {
            $sql .= ' AND f.user_id = ?';
            $params[] = $userId;
        }
        if ($localId !== null) {
            $sql .= ' AND f.local_id = ?';
            $params[] = $localId;
        }
        return self::fetchAll($sql . ' ORDER BY f.fecha_vencimiento, l.nombre, p.nombre, f.producto_texto LIMIT 500', $params);
    }

    /** Por local: cuántas fechas pendientes y cuántas urgentes (vencidas o dentro de $diasUrgente). */
    public static function contarPorLocal(?int $userId, int $diasUrgente): array
    {
        $sql = 'SELECT local_id, COUNT(*) AS total, SUM(fecha_vencimiento <= ?) AS urgentes
                FROM fechas WHERE retirado_at IS NULL AND fecha_vencimiento >= ?';
        $params = [date('Y-m-d', strtotime("+{$diasUrgente} days")), date('Y-m-d', strtotime('-30 days'))];
        if ($userId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        $cuenta = [];
        foreach (self::fetchAll($sql . ' GROUP BY local_id', $params) as $f) {
            $cuenta[(int) $f['local_id']] = ['total' => (int) $f['total'], 'urgentes' => (int) $f['urgentes']];
        }
        return $cuenta;
    }

    /** Las fechas de un producto (para su ficha). */
    public static function delProducto(int $productoId, ?int $userId): array
    {
        $sql = self::SELECT . ' WHERE f.producto_id = ? AND f.retirado_at IS NULL';
        $params = [$productoId];
        if ($userId !== null) {
            $sql .= ' AND f.user_id = ?';
            $params[] = $userId;
        }
        return self::fetchAll($sql . ' ORDER BY f.fecha_vencimiento LIMIT 50', $params);
    }

    /** "Ya lo saqué / se vendió": deja de aparecer, pero queda guardado. */
    public static function retirar(int $id): void
    {
        self::execute('UPDATE fechas SET retirado_at = NOW() WHERE id = ? AND retirado_at IS NULL', [$id]);
    }

    public static function eliminar(int $id): void
    {
        self::execute('DELETE FROM fechas WHERE id = ?', [$id]);
    }

    /** Fechas que entraron en los días de aviso de su dueño y todavía no se avisaron. */
    public static function paraAvisar(): array
    {
        return self::fetchAll(
            "SELECT f.id, f.user_id
             FROM fechas f
             JOIN users u ON u.id = f.user_id AND u.activo = 1 AND u.deleted_at IS NULL
             WHERE f.retirado_at IS NULL AND f.avisado_at IS NULL
               AND f.fecha_vencimiento >= CURDATE() - INTERVAL 1 DAY
               AND f.fecha_vencimiento <= CURDATE() + INTERVAL u.dias_aviso DAY"
        );
    }

    /** @param int[] $ids */
    public static function marcarAvisadas(array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        self::execute("UPDATE fechas SET avisado_at = NOW() WHERE id IN ({$marcas})", array_values($ids));
    }

    /** Nombre para mostrar: el del catálogo o lo que se escribió. */
    public static function nombre(array $f): string
    {
        return $f['producto_id'] !== null && $f['nombre'] !== null
            ? Producto::nombreCompleto($f)
            : (string) $f['producto_texto'];
    }

    /** ['dias' => n, 'clase' => venc-*, 'etiqueta' => "Vence en 3 días"] */
    public static function estado(string $fecha): array
    {
        $dias = (int) round((strtotime($fecha) - strtotime(date('Y-m-d'))) / 86400);
        $critico = (int) Configuracion::get('vencimiento_dias_critico', '3');
        $proximo = (int) Configuracion::get('vencimiento_dias_proximo', '15');
        return ['dias' => $dias] + match (true) {
            $dias < 0         => ['clase' => 'venc-vencido', 'etiqueta' => 'Vencido hace ' . self::dias(-$dias)],
            $dias === 0       => ['clase' => 'venc-hoy', 'etiqueta' => 'Vence hoy'],
            $dias === 1       => ['clase' => 'venc-pocos', 'etiqueta' => 'Vence mañana'],
            $dias <= $critico => ['clase' => 'venc-pocos', 'etiqueta' => 'En ' . self::dias($dias)],
            $dias <= $proximo => ['clase' => 'venc-proximo', 'etiqueta' => 'En ' . self::dias($dias)],
            default           => ['clase' => 'venc-ok', 'etiqueta' => 'En ' . self::dias($dias)],
        };
    }

    private static function dias(int $n): string
    {
        return $n === 1 ? '1 día' : "{$n} días";
    }
}
