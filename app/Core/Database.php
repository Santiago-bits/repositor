<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                APP_ENV === 'local'
                    ? 'No se pudo conectar a MySQL (' . $e->getMessage() . '). ¿Está encendido MySQL en XAMPP y corriste database/migrate.php?'
                    : 'No se pudo conectar a la base de datos.',
                0,
                $e
            );
        }

        $pdo->exec("SET time_zone = '" . DB_TIMEZONE . "', NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

        return self::$pdo = $pdo;
    }

    /** Ejecuta $callback dentro de una transacción. */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
