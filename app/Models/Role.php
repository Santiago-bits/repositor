<?php
declare(strict_types=1);

namespace App\Models;

final class Role extends Model
{
    public static function all(): array
    {
        return self::fetchAll('SELECT id, slug, nombre FROM roles ORDER BY id');
    }

    public static function find(int $id): ?array
    {
        return self::fetch('SELECT id, slug, nombre FROM roles WHERE id = ?', [$id]);
    }
}
