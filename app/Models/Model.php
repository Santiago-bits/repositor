<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected static function db(): PDO
    {
        return Database::connection();
    }

    protected static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    protected static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected static function value(string $sql, array $params = []): mixed
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    protected static function execute(string $sql, array $params = []): int
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected static function insert(string $sql, array $params = []): int
    {
        self::execute($sql, $params);
        return (int) self::db()->lastInsertId();
    }
}
