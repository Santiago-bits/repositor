<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Ejecuta las migraciones pendientes de database/migrations (en orden).
 * Lo usan database/migrate.php y la web: después de subir cambios al servidor,
 * la primera visita aplica lo que falte, sin tener que entrar por SSH.
 */
final class Migrador
{
    private const CARPETA = BASE_PATH . '/database/migrations';
    private const MARCA = BASE_PATH . '/storage/migraciones.version';

    /** @return string[] archivos ejecutados */
    public static function migrar(): array
    {
        $db = Database::connection();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS migraciones (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                archivo VARCHAR(190) NOT NULL UNIQUE,
                ejecutada_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $hechas = $db->query('SELECT archivo FROM migraciones')->fetchAll(PDO::FETCH_COLUMN);
        $ejecutadas = [];
        foreach (self::archivos() as $ruta) {
            $archivo = basename($ruta);
            if (in_array($archivo, $hechas, true)) {
                continue;
            }

            $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($ruta));
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $sentencia) {
                if (trim($sentencia) !== '') {
                    $db->exec($sentencia);
                }
            }
            $db->prepare('INSERT INTO migraciones (archivo) VALUES (?)')->execute([$archivo]);
            $ejecutadas[] = $archivo;
        }

        @file_put_contents(self::MARCA, self::ultima());
        return $ejecutadas;
    }

    /**
     * Para cada pedido web: solo compara el nombre de la última migración con una marca
     * en storage/ (no toca la base). Si hay una nueva, migra una sola vez (con candado).
     */
    public static function siHaceFalta(): void
    {
        $ultima = self::ultima();
        if ($ultima === '' || @file_get_contents(self::MARCA) === $ultima) {
            return;
        }

        $candado = @fopen(self::MARCA . '.lock', 'c');
        if ($candado === false || !flock($candado, LOCK_EX | LOCK_NB)) {
            return; // otro pedido ya está migrando
        }
        try {
            if (@file_get_contents(self::MARCA) !== $ultima) {
                self::migrar();
            }
        } catch (\Throwable $e) {
            error_log('Migración automática: ' . $e->getMessage());
        } finally {
            flock($candado, LOCK_UN);
            fclose($candado);
        }
    }

    /** @return string[] */
    private static function archivos(): array
    {
        $archivos = glob(self::CARPETA . '/*.sql') ?: [];
        sort($archivos);
        return $archivos;
    }

    private static function ultima(): string
    {
        $archivos = self::archivos();
        return $archivos === [] ? '' : basename(end($archivos));
    }
}
