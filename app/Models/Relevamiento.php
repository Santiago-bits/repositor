<?php
declare(strict_types=1);

namespace App\Models;

/** Una visita a un local (relevamiento). */
final class Relevamiento extends Model
{
    public const ESTADOS = [
        'en_proceso' => ['En proceso', 'text-bg-warning'],
        'finalizado' => ['Finalizado', 'text-bg-success'],
        'cancelado'  => ['Cancelado', 'text-bg-secondary'],
    ];

    private const SELECT = "SELECT r.id, r.user_id, r.local_id, r.fecha, r.inicio_at, r.fin_at, r.estado,
                                   l.nombre AS local, l.tipo, l.direccion,
                                   CONCAT(u.nombre, ' ', u.apellido) AS usuario,
                                   (SELECT COUNT(*) FROM relevamiento_productos rp WHERE rp.relevamiento_id = r.id) AS productos
                            FROM relevamientos r
                            JOIN locales l ON l.id = r.local_id
                            JOIN users u ON u.id = r.user_id";

    public static function find(int $id): ?array
    {
        return self::fetch(self::SELECT . ' WHERE r.id = ?', [$id]);
    }

    /** La visita en curso del usuario (como mucho una). */
    public static function abiertaDeUsuario(int $userId): ?array
    {
        return self::fetch(
            self::SELECT . " WHERE r.user_id = ? AND r.estado = 'en_proceso' ORDER BY r.inicio_at DESC LIMIT 1",
            [$userId]
        );
    }

    /** Visitas finalizadas hoy por el usuario. */
    public static function finalizadasHoy(int $userId): array
    {
        return self::fetchAll(
            self::SELECT . " WHERE r.user_id = ? AND r.fecha = CURDATE() AND r.estado = 'finalizado' ORDER BY r.inicio_at",
            [$userId]
        );
    }

    /** Visitas del usuario desde una fecha, con cuántos productos fueron conteo de promociones. */
    public static function historial(int $userId, string $desde): array
    {
        return self::fetchAll(
            "SELECT r.id, r.fecha, r.inicio_at, r.fin_at, r.estado, l.nombre AS local, l.tipo,
                    (SELECT COUNT(*) FROM relevamiento_productos rp WHERE rp.relevamiento_id = r.id) AS productos,
                    (SELECT COUNT(*) FROM relevamiento_productos rp WHERE rp.relevamiento_id = r.id AND rp.promocion_id IS NOT NULL) AS conteo,
                    (SELECT COUNT(*) FROM fotos f WHERE f.relevamiento_id = r.id AND f.deleted_at IS NULL) AS fotos
             FROM relevamientos r
             JOIN locales l ON l.id = r.local_id
             WHERE r.user_id = ? AND r.fecha >= ?
             ORDER BY r.inicio_at DESC
             LIMIT 500",
            [$userId, $desde]
        );
    }

    /** "Conteo de promociones", "Reposición y conteo", "Reposición" o "Visita". */
    public static function actividad(array $v): string
    {
        $otros = (int) $v['productos'] - (int) $v['conteo'];
        return match (true) {
            $v['conteo'] > 0 && $otros > 0 => 'Reposición y conteo de promociones',
            $v['conteo'] > 0               => 'Conteo de promociones',
            $v['productos'] > 0            => 'Reposición',
            default                        => 'Visita',
        };
    }

    /** @param array{desde: string, hasta: string, local_id: ?int, user_id: ?int, estado: ?string} $f */
    public static function buscar(array $f, int $limite = 300): array
    {
        $sql = self::SELECT . ' WHERE r.fecha BETWEEN ? AND ?';
        $params = [$f['desde'], $f['hasta']];
        foreach (['local_id' => 'r.local_id', 'user_id' => 'r.user_id', 'estado' => 'r.estado'] as $clave => $columna) {
            if (!empty($f[$clave])) {
                $sql .= " AND {$columna} = ?";
                $params[] = $f[$clave];
            }
        }
        return self::fetchAll($sql . ' ORDER BY r.inicio_at DESC LIMIT ' . max(1, $limite), $params);
    }

    public static function create(int $userId, int $localId, ?float $lat, ?float $lng): int
    {
        return self::insert(
            'INSERT INTO relevamientos (user_id, local_id, fecha, inicio_at, lat_inicio, lng_inicio)
             VALUES (?, ?, CURDATE(), NOW(), ?, ?)',
            [$userId, $localId, $lat, $lng]
        );
    }

    public static function cerrar(int $id, string $estado, string $fin): void
    {
        self::execute(
            "UPDATE relevamientos SET estado = ?, fin_at = ? WHERE id = ? AND estado = 'en_proceso'",
            [$estado, $fin, $id]
        );
    }

    /** Contadores de la visita (productos, estados de stock, vencimientos, fotos, observaciones). */
    public static function resumen(int $id): array
    {
        $r = self::fetch(
            "SELECT COUNT(*) AS productos,
                    COALESCE(SUM(stock > 0), 0) AS con_stock,
                    COALESCE(SUM(estado_stock = 'bajo'), 0) AS bajo,
                    COALESCE(SUM(estado_stock = 'sin_stock'), 0) AS sin_stock,
                    COALESCE(SUM(estado_stock = 'no_exhibido'), 0) AS no_exhibido,
                    COALESCE(SUM(con_problema), 0) AS con_problema,
                    (SELECT COUNT(*) FROM vencimientos v JOIN relevamiento_productos x ON x.id = v.relevamiento_producto_id
                      WHERE x.relevamiento_id = ?) AS vencimientos,
                    (SELECT COUNT(*) FROM fotos f WHERE f.relevamiento_id = ? AND f.deleted_at IS NULL) AS fotos,
                    (SELECT COUNT(*) FROM observaciones o WHERE o.relevamiento_id = ?) AS observaciones
             FROM relevamiento_productos
             WHERE relevamiento_id = ?",
            [$id, $id, $id, $id]
        );
        return array_map('intval', $r ?? []);
    }

    /** Momento del último registro cargado en la visita (null si no se cargó nada). */
    public static function ultimaActividad(int $id): ?string
    {
        $valor = self::value(
            'SELECT MAX(momento) FROM (
                SELECT COALESCE(updated_at, created_at) AS momento FROM relevamiento_productos WHERE relevamiento_id = ?
                UNION ALL SELECT created_at FROM fotos WHERE relevamiento_id = ?
                UNION ALL SELECT COALESCE(updated_at, created_at) FROM observaciones WHERE relevamiento_id = ?
             ) actividad',
            [$id, $id, $id]
        );
        return $valor ?: null;
    }
}
