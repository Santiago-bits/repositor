<?php
/**
 * Crea un usuario administrador (pensado para el primer ingreso en Hostinger).
 *
 *   php database/crear-admin.php "Nombre" "Apellido" email@dominio.com
 *
 * La contraseña se pide por teclado para que no quede en el historial de comandos.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Database;

[, $nombre, $apellido, $email] = array_pad($argv, 4, null);
$email = mb_strtolower(trim((string) $email));

if (!$nombre || !$apellido || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php database/crear-admin.php \"Nombre\" \"Apellido\" email@dominio.com\n");
    exit(1);
}

echo 'Contraseña (mínimo 8 caracteres): ';
$password = trim((string) fgets(STDIN));
if (mb_strlen($password) < 8) {
    fwrite(STDERR, "La contraseña tiene que tener al menos 8 caracteres.\n");
    exit(1);
}

$db = Database::connection();
$existe = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$existe->execute([$email]);
if ((int) $existe->fetchColumn() > 0) {
    fwrite(STDERR, "Ya existe un usuario con ese email.\n");
    exit(1);
}

$roleId = (int) $db->query("SELECT id FROM roles WHERE slug = 'admin'")->fetchColumn();
$db->prepare('INSERT INTO users (role_id, nombre, apellido, email, password_hash) VALUES (?, ?, ?, ?, ?)')
    ->execute([$roleId, trim($nombre), trim($apellido), $email, password_hash($password, PASSWORD_DEFAULT)]);

echo "Administrador creado: {$email}\n";
