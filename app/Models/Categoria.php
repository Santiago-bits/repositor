<?php
declare(strict_types=1);

namespace App\Models;

/** Dos niveles: categoría principal (parent_id NULL) y subcategoría. */
final class Categoria extends Model
{
    public static function find(int $id): ?array
    {
        return self::fetch('SELECT id, parent_id, nombre, activo FROM categorias WHERE id = ?', [$id]);
    }

    /** Principales con sus subcategorías y la cantidad de productos de cada una. */
    public static function arbol(): array
    {
        $filas = self::fetchAll(
            'SELECT c.id, c.parent_id, c.nombre, c.activo,
                    (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id AND p.deleted_at IS NULL) AS productos
             FROM categorias c
             ORDER BY c.nombre'
        );

        $principales = [];
        foreach ($filas as $f) {
            if ($f['parent_id'] === null) {
                $principales[$f['id']] = $f + ['hijas' => []];
            }
        }
        foreach ($filas as $f) {
            if ($f['parent_id'] !== null && isset($principales[$f['parent_id']])) {
                $principales[$f['parent_id']]['hijas'][] = $f;
            }
        }
        return array_values($principales);
    }

    public static function principales(): array
    {
        return self::fetchAll('SELECT id, nombre, activo FROM categorias WHERE parent_id IS NULL ORDER BY nombre');
    }

    /** Opciones para el select de productos: [id => "Principal › Sub"]. */
    public static function opciones(?int $incluir = null): array
    {
        $filas = self::fetchAll(
            'SELECT c.id, c.nombre, p.nombre AS padre
             FROM categorias c
             LEFT JOIN categorias p ON p.id = c.parent_id
             WHERE c.activo = 1 OR c.id = ?
             ORDER BY COALESCE(p.nombre, c.nombre), c.parent_id IS NOT NULL, c.nombre',
            [$incluir ?? 0]
        );

        $opciones = [];
        foreach ($filas as $f) {
            $opciones[(int) $f['id']] = $f['padre'] ? "{$f['padre']} › {$f['nombre']}" : $f['nombre'];
        }
        return $opciones;
    }

    public static function existeNombre(string $nombre, ?int $parentId, ?int $exceptId = null): bool
    {
        return (bool) self::value(
            'SELECT COUNT(*) FROM categorias WHERE nombre = ? AND parent_id <=> ? AND id <> ?',
            [$nombre, $parentId, $exceptId ?? 0]
        );
    }

    public static function tieneHijas(int $id): bool
    {
        return (bool) self::value('SELECT COUNT(*) FROM categorias WHERE parent_id = ?', [$id]);
    }

    public static function create(array $d): int
    {
        return self::insert(
            'INSERT INTO categorias (parent_id, nombre, activo) VALUES (?, ?, ?)',
            [$d['parent_id'], $d['nombre'], (int) $d['activo']]
        );
    }

    public static function update(int $id, array $d): void
    {
        self::execute(
            'UPDATE categorias SET parent_id = ?, nombre = ?, activo = ? WHERE id = ?',
            [$d['parent_id'], $d['nombre'], (int) $d['activo'], $id]
        );
    }

    public static function setActivo(int $id, bool $activo): void
    {
        self::execute('UPDATE categorias SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }
}
