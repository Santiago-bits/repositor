<?php
/**
 * Ejecuta las migraciones pendientes de database/migrations (en orden).
 *
 *   c:\xampp\php\php.exe database/migrate.php            → migra
 *   c:\xampp\php\php.exe database/migrate.php --fresh    → (solo local) borra la base y la crea de cero
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Database;

$fresh = in_array('--fresh', $argv, true);
if ($fresh && APP_ENV !== 'local') {
    fwrite(STDERR, "--fresh solo está permitido en local.\n");
    exit(1);
}

// En local la base se crea sola; en Hostinger se crea desde hPanel.
if (APP_ENV === 'local') {
    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    if ($fresh) {
        $server->exec('DROP DATABASE IF EXISTS `' . DB_NAME . '`');
        echo "Base `" . DB_NAME . "` eliminada.\n";
    }
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}

$db = Database::connection();
$db->exec(
    'CREATE TABLE IF NOT EXISTS migraciones (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        archivo VARCHAR(190) NOT NULL UNIQUE,
        ejecutada_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$hechas = $db->query('SELECT archivo FROM migraciones')->fetchAll(PDO::FETCH_COLUMN);
$archivos = glob(__DIR__ . '/migrations/*.sql');
sort($archivos);

$ejecutadas = 0;
foreach ($archivos as $ruta) {
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
    echo "  OK  {$archivo}\n";
    $ejecutadas++;
}

echo $ejecutadas > 0 ? "Migraciones ejecutadas: {$ejecutadas}\n" : "No hay migraciones pendientes.\n";
