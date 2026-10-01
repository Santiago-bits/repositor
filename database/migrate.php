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

$ejecutadas = App\Core\Migrador::migrar();
foreach ($ejecutadas as $archivo) {
    echo "  OK  {$archivo}\n";
}
echo $ejecutadas ? 'Migraciones ejecutadas: ' . count($ejecutadas) . "\n" : "No hay migraciones pendientes.\n";
