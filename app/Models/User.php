<?php
declare(strict_types=1);

namespace App\Models;

final class User extends Model
{
    private const SELECT = 'SELECT u.id, u.role_id, u.nombre, u.apellido, u.email, u.activo, u.last_login_at, u.created_at,
                                   r.slug AS role_slug, r.nombre AS role_nombre
                            FROM users u
                            JOIN roles r ON r.id = u.role_id';

    public static function find(int $id): ?array
    {
        return self::fetch(self::SELECT . ' WHERE u.id = ? AND u.deleted_at IS NULL', [$id]);
    }

    public static function findActive(int $id): ?array
    {
        return self::fetch(self::SELECT . ' WHERE u.id = ? AND u.activo = 1 AND u.deleted_at IS NULL', [$id]);
    }

    /** Solo para el login: incluye el hash de la contraseña. */
    public static function findForLogin(string $email): ?array
    {
        return self::fetch(
            'SELECT id, activo, password_hash FROM users WHERE email = ? AND deleted_at IS NULL',
            [$email]
        );
    }

    public static function all(): array
    {
        return self::fetchAll(
            'SELECT u.id, u.nombre, u.apellido, u.email, u.activo, u.last_login_at,
                    r.slug AS role_slug, r.nombre AS role_nombre,
                    (SELECT COUNT(*) FROM local_user lu WHERE lu.user_id = u.id) AS locales_count
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.deleted_at IS NULL
             ORDER BY u.activo DESC, u.nombre, u.apellido'
        );
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        return (bool) self::value(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
            [$email, $exceptId ?? 0]
        );
    }

    public static function create(array $d): int
    {
        return self::insert(
            'INSERT INTO users (role_id, nombre, apellido, email, password_hash, activo) VALUES (?, ?, ?, ?, ?, ?)',
            [$d['role_id'], $d['nombre'], $d['apellido'], $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), (int) $d['activo']]
        );
    }

    public static function update(int $id, array $d): void
    {
        self::execute(
            'UPDATE users SET role_id = ?, nombre = ?, apellido = ?, email = ?, activo = ? WHERE id = ?',
            [$d['role_id'], $d['nombre'], $d['apellido'], $d['email'], (int) $d['activo'], $id]
        );
        if (!empty($d['password'])) {
            self::updatePassword($id, $d['password']);
        }
    }

    public static function updatePassword(int $id, string $password): void
    {
        self::execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function setActivo(int $id, bool $activo): void
    {
        self::execute('UPDATE users SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }

    /** Se oculta y se libera el email (para poder volver a usarlo). */
    public static function eliminar(int $id): void
    {
        self::execute(
            "UPDATE users SET deleted_at = NOW(), activo = 0, email = LEFT(CONCAT('eliminado', id, '.', email), 150) WHERE id = ?",
            [$id]
        );
    }

    public static function touchLogin(int $id): void
    {
        self::execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    /** @return int[] */
    public static function localIds(int $id): array
    {
        return array_map('intval', array_column(
            self::fetchAll('SELECT local_id FROM local_user WHERE user_id = ?', [$id]),
            'local_id'
        ));
    }

    /** @param int[] $localIds */
    public static function syncLocales(int $id, array $localIds): void
    {
        self::execute('DELETE FROM local_user WHERE user_id = ?', [$id]);
        foreach ($localIds as $localId) {
            self::execute('INSERT INTO local_user (user_id, local_id) VALUES (?, ?)', [$id, $localId]);
        }
    }
}
