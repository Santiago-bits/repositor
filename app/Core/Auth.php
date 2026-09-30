<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Models\UserToken;

final class Auth
{
    private const COOKIE = 'jacob_remember';

    private static ?array $user = null;
    private static bool $resolved = false;

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = Session::get('user_id');
        if ($id !== null) {
            self::$user = User::findActive((int) $id);
            if (self::$user === null) {
                Session::pull('user_id');
            }
        } elseif (!empty($_COOKIE[self::COOKIE])) {
            self::$user = self::fromRememberCookie((string) $_COOKIE[self::COOKIE]);
        }

        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role_slug'] ?? null) === 'admin';
    }

    public static function login(int $userId, bool $remember): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
        User::touchLogin($userId);
        if ($remember) {
            self::issueRememberToken($userId);
        }
        self::$user = User::findActive($userId);
        self::$resolved = true;
    }

    public static function logout(): void
    {
        if (!empty($_COOKIE[self::COOKIE])) {
            UserToken::delete(explode(':', (string) $_COOKIE[self::COOKIE])[0]);
        }
        self::forgetCookie();
        Session::destroy();
        self::$user = null;
    }

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expires = time() + REMEMBER_DAYS * 86400;

        UserToken::create($userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires));

        setcookie(self::COOKIE, $selector . ':' . $validator, [
            'expires'  => $expires,
            'path'     => Session::cookiePath(),
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** Recupera la sesión desde la cookie "mantener sesión" y rota el token. */
    private static function fromRememberCookie(string $cookie): ?array
    {
        [$selector, $validator] = array_pad(explode(':', $cookie, 2), 2, '');
        if (!ctype_xdigit($selector) || !ctype_xdigit($validator)) {
            self::forgetCookie();
            return null;
        }

        $token = UserToken::findValid($selector);
        if ($token === null || !hash_equals($token['validator_hash'], hash('sha256', $validator))) {
            self::forgetCookie();
            return null;
        }

        UserToken::delete($selector);
        $user = User::findActive((int) $token['user_id']);
        if ($user === null) {
            self::forgetCookie();
            return null;
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        self::issueRememberToken((int) $user['id']);

        return $user;
    }

    private static function forgetCookie(): void
    {
        setcookie(self::COOKIE, '', ['expires' => time() - 3600, 'path' => Session::cookiePath()]);
        unset($_COOKIE[self::COOKIE]);
    }
}
