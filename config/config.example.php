<?php
/**
 * Configuración de JACOB — PLANTILLA.
 * Copiá este archivo como config/config.php y completá los datos del servidor.
 * (config/config.php no se sube al repositorio porque tiene contraseñas.)
 *
 * Se detecta el entorno por sistema operativo: XAMPP corre en Windows y Hostinger en Linux.
 * Así funciona igual entrando por localhost, por la IP de la red (celular) o desde la consola.
 */

if (PHP_OS_FAMILY === 'Windows') {
    // ── LOCAL (XAMPP) ─────────────────────────────────────────────
    define('APP_ENV', 'local');
    define('APP_DEBUG', true);
    define('BASE_URL', '/repositor');

    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', 3306);
    define('DB_NAME', 'jacob');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // ── SERVIDOR (Hostinger · baseocho.tienda) ────────────────────
    define('APP_ENV', 'production');
    define('APP_DEBUG', false);
    define('BASE_URL', '');

    // hPanel → Bases de datos → Administración: copiar nombre, usuario y contraseña.
    define('DB_HOST', 'localhost');
    define('DB_PORT', 3306);
    define('DB_NAME', 'u000000000_jacob');
    define('DB_USER', 'u000000000_jacob');
    define('DB_PASS', 'CAMBIAR_EN_EL_SERVIDOR');
}

define('APP_NAME', 'JACOB');
define('APP_TAGLINE', 'Gestión inteligente de reposición');
define('APP_TIMEZONE', 'America/Argentina/Buenos_Aires');
define('DB_TIMEZONE', '-03:00');

define('REMEMBER_DAYS', 30);
define('LOGIN_MAX_INTENTOS', 5);
define('LOGIN_BLOQUEO_MINUTOS', 15);
