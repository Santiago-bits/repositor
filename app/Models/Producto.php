<?php
declare(strict_types=1);

namespace App\Models;

final class Producto extends Model
{
    /** Bebidas: se venden por volumen. */
    public const UNIDADES = [
        'ml' => 'Mililitros (ml)',
        'cc' => 'Centímetros cúbicos (cc)',
        'l'  => 'Litros (L)',
        'u'  => 'Unidades',
    ];

    private const SELECT = 'SELECT p.id, p.nombre, p.marca, p.codigo_barras, p.categoria_id, p.presentacion,
                                   p.unidad_medida, p.imagen_path, p.descripcion, p.activo, p.created_at,
                                   c.nombre AS categoria, cp.nombre AS categoria_padre
                            FROM productos p
                            LEFT JOIN categorias c ON c.id = p.categoria_id
                            LEFT JOIN categorias cp ON cp.id = c.parent_id';

    public static function find(int $id): ?array
    {
        return self::fetch(self::SELECT . ' WHERE p.id = ? AND p.deleted_at IS NULL', [$id]);
    }

    public static function findByCodigo(string $codigo, bool $soloActivos = true): ?array
    {
        return self::fetch(
            self::SELECT . ' WHERE p.codigo_barras = ? AND p.deleted_at IS NULL' . ($soloActivos ? ' AND p.activo = 1' : ''),
            [$codigo]
        );
    }

    /**
     * Búsqueda por nombre, marca, presentación, código o categoría.
     * Cada palabra tiene que aparecer en algún campo: "mirin 500" encuentra "Mirin · 500 ml".
     */
    public static function buscar(string $q, bool $soloActivos = true, int $limite = 20): array
    {
        $terminos = array_slice(preg_split('/\s+/', trim(mb_substr($q, 0, 80)), -1, PREG_SPLIT_NO_EMPTY), 0, 5);
        if ($terminos === []) {
            return [];
        }

        $where = [];
        $params = [];
        foreach ($terminos as $t) {
            $like = '%' . addcslashes($t, '%_\\') . '%';
            $where[] = '(p.nombre LIKE ? OR p.marca LIKE ? OR p.presentacion LIKE ? OR p.codigo_barras LIKE ?
                         OR c.nombre LIKE ? OR cp.nombre LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        // Primero el código exacto, después los que empiezan con la primera palabra.
        array_push($params, trim($q), addcslashes($terminos[0], '%_\\') . '%');

        return self::fetchAll(
            self::SELECT . ' WHERE p.deleted_at IS NULL' . ($soloActivos ? ' AND p.activo = 1' : '') . '
               AND ' . implode(' AND ', $where) . '
             ORDER BY (p.codigo_barras = ?) DESC, (p.nombre LIKE ?) DESC, p.activo DESC, p.nombre, p.presentacion
             LIMIT ' . max(1, $limite),
            $params
        );
    }

    public static function todos(int $limite = 500): array
    {
        return self::fetchAll(
            self::SELECT . ' WHERE p.deleted_at IS NULL ORDER BY p.activo DESC, p.nombre, p.presentacion LIMIT ' . max(1, $limite)
        );
    }

    public static function codigoExiste(string $codigo, ?int $exceptId = null): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM productos WHERE codigo_barras = ? AND id <> ?', [$codigo, $exceptId ?? 0]);
    }

    /** Mismo nombre + marca + presentación = mismo producto (evita duplicados). */
    public static function duplicado(string $nombre, ?string $marca, ?string $presentacion, ?int $exceptId = null): ?array
    {
        return self::fetch(
            "SELECT id, nombre, presentacion FROM productos
             WHERE deleted_at IS NULL AND nombre = ?
               AND COALESCE(marca, '') = ? AND COALESCE(presentacion, '') = ? AND id <> ?
             LIMIT 1",
            [$nombre, $marca ?? '', $presentacion ?? '', $exceptId ?? 0]
        );
    }

    public static function create(array $d): int
    {
        return self::insert(
            'INSERT INTO productos (nombre, marca, codigo_barras, categoria_id, presentacion, unidad_medida, descripcion, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            self::params($d)
        );
    }

    public static function update(int $id, array $d): void
    {
        self::execute(
            'UPDATE productos SET nombre = ?, marca = ?, codigo_barras = ?, categoria_id = ?, presentacion = ?,
                    unidad_medida = ?, descripcion = ?, activo = ?
             WHERE id = ?',
            [...self::params($d), $id]
        );
    }

    public static function setImagen(int $id, ?string $path): void
    {
        self::execute('UPDATE productos SET imagen_path = ? WHERE id = ?', [$path, $id]);
    }

    /**
     * Se oculta (no se borra la fila) para no romper el historial de visitas.
     * Se libera el código de barras para poder cargarlo de nuevo y se borran sus promos.
     */
    public static function eliminar(int $id): void
    {
        self::execute('UPDATE productos SET deleted_at = NOW(), activo = 0, codigo_barras = NULL WHERE id = ?', [$id]);
        self::execute('UPDATE promociones SET deleted_at = NOW() WHERE producto_id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function setActivo(int $id, bool $activo): void
    {
        self::execute('UPDATE productos SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }

    /** Stock registrado en visitas. Para el repositor, solo de sus locales. */
    public static function historial(int $productoId, array $user, int $limite = 15): array
    {
        $esAdmin = $user['role_slug'] === 'admin';
        $params = $esAdmin ? [$productoId] : [$user['id'], $productoId];

        return self::fetchAll(
            "SELECT r.fecha, l.nombre AS local, rp.stock, rp.estado_stock
             FROM relevamiento_productos rp
             JOIN relevamientos r ON r.id = rp.relevamiento_id
             JOIN locales l ON l.id = r.local_id
             " . ($esAdmin ? '' : 'JOIN local_user lu ON lu.local_id = r.local_id AND lu.user_id = ?') . "
             WHERE rp.producto_id = ? AND r.estado <> 'cancelado'
             ORDER BY r.inicio_at DESC
             LIMIT " . max(1, $limite),
            $params
        );
    }

    /** "Mirin 500 ml" */
    public static function nombreCompleto(array $p): string
    {
        return trim($p['nombre'] . ' ' . ($p['presentacion'] ?? ''));
    }

    public static function categoriaCompleta(array $p): ?string
    {
        if (empty($p['categoria'])) {
            return null;
        }
        return $p['categoria_padre'] ? "{$p['categoria_padre']} › {$p['categoria']}" : $p['categoria'];
    }

    private static function params(array $d): array
    {
        return [
            $d['nombre'], $d['marca'], $d['codigo_barras'], $d['categoria_id'], $d['presentacion'],
            $d['unidad_medida'], $d['descripcion'], (int) $d['activo'],
        ];
    }
}
