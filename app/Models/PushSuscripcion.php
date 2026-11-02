<?php
declare(strict_types=1);

namespace App\Models;

/** Celulares donde un usuario activó los avisos. */
final class PushSuscripcion extends Model
{
    public static function guardar(int $userId, string $endpoint, string $p256dh, string $auth): void
    {
        self::execute(
            'INSERT INTO push_suscripciones (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth)',
            [$userId, $endpoint, $p256dh, $auth]
        );
    }

    public static function deUsuario(int $userId): array
    {
        return self::fetchAll('SELECT id, endpoint FROM push_suscripciones WHERE user_id = ?', [$userId]);
    }

    public static function contar(int $userId): int
    {
        return (int) self::value('SELECT COUNT(*) FROM push_suscripciones WHERE user_id = ?', [$userId]);
    }

    public static function eliminarEndpoint(string $endpoint, ?int $userId = null): void
    {
        $userId === null
            ? self::execute('DELETE FROM push_suscripciones WHERE endpoint = ?', [$endpoint])
            : self::execute('DELETE FROM push_suscripciones WHERE endpoint = ? AND user_id = ?', [$endpoint, $userId]);
    }
}
