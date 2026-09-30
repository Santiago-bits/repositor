<?php
declare(strict_types=1);

namespace App\Models;

/** Intentos fallidos de login por IP, para frenar ataques de fuerza bruta. */
final class LoginIntento extends Model
{
    public static function recientes(string $ip, int $minutos): int
    {
        return (int) self::value(
            'SELECT COUNT(*) FROM login_intentos WHERE ip = ? AND created_at > NOW() - INTERVAL ? MINUTE',
            [$ip, $minutos]
        );
    }

    public static function registrar(string $ip, string $email): void
    {
        self::execute('INSERT INTO login_intentos (ip, email) VALUES (?, ?)', [$ip, mb_substr($email, 0, 150)]);
        self::execute('DELETE FROM login_intentos WHERE created_at < NOW() - INTERVAL 1 DAY');
    }

    public static function limpiar(string $ip): void
    {
        self::execute('DELETE FROM login_intentos WHERE ip = ?', [$ip]);
    }
}
