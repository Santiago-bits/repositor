<?php
declare(strict_types=1);

namespace App\Models;

/** Tokens de "mantener sesión iniciada" (se guarda solo el hash del validador). */
final class UserToken extends Model
{
    public static function create(int $userId, string $selector, string $validatorHash, string $expiresAt): void
    {
        self::execute(
            'INSERT INTO user_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)',
            [$userId, $selector, $validatorHash, $expiresAt]
        );
    }

    public static function findValid(string $selector): ?array
    {
        return self::fetch(
            'SELECT user_id, validator_hash FROM user_tokens WHERE selector = ? AND expires_at > NOW()',
            [$selector]
        );
    }

    public static function delete(string $selector): void
    {
        self::execute('DELETE FROM user_tokens WHERE selector = ?', [$selector]);
    }

    public static function deleteForUser(int $userId): void
    {
        self::execute('DELETE FROM user_tokens WHERE user_id = ?', [$userId]);
    }
}
