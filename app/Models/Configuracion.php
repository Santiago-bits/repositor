<?php
declare(strict_types=1);

namespace App\Models;

final class Configuracion extends Model
{
    private static ?array $cache = null;

    public static function get(string $clave, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            self::$cache = array_column(self::fetchAll('SELECT clave, valor FROM configuracion'), 'valor', 'clave');
        }
        return self::$cache[$clave] ?? $default;
    }

    public static function set(string $clave, string $valor): void
    {
        self::execute(
            'INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)',
            [$clave, $valor]
        );
        self::$cache = null;
    }
}
