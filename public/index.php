<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

use App\Core\Migrador;
use App\Core\Router;
use App\Core\Session;
use App\Services\AvisoService;

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Después de un deploy, aplica sola las migraciones nuevas (una vez).
Migrador::siHaceFalta();

Session::start();

// Avisos de fechas por vencer: se revisan solos como mucho una vez por hora.
AvisoService::siCorresponde();

$router = new Router();
require BASE_PATH . '/routes.php';
$router->dispatch();
