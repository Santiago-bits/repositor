<?php
declare(strict_types=1);

/**
 * Manda los avisos de fechas por vencer. Para la tarea programada (cron) de Hostinger, cada hora:
 *     php /home/USUARIO/domains/DOMINIO/public_html/database/enviar-avisos.php
 * Sin cron también funciona: la app revisa sola cuando alguien la abre (como mucho una vez por hora).
 */
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Models\Configuracion;
use App\Services\AvisoService;

$r = AvisoService::enviarPendientes();
Configuracion::set('avisos_ultimo', date('Y-m-d H:i:s'));
echo date('Y-m-d H:i') . " · usuarios con fechas por avisar: {$r['usuarios']} · avisados: {$r['avisados']}\n";
