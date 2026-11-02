<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\PushSuscripcion;
use App\Services\AvisoService;
use App\Services\WebPushService;
use Throwable;

/** Avisos en el celular: activar, apagar, probar y lo que muestra cada notificación. */
final class AvisoController extends Controller
{
    public function clave(): never
    {
        try {
            $this->json(['clave' => WebPushService::clavePublica()]);
        } catch (Throwable $e) {
            $this->json(['message' => $e->getMessage()], 500);
        }
    }

    public function suscribir(): never
    {
        $endpoint = (string) Request::input('endpoint', '');
        $p256dh = (string) Request::input('p256dh', '');
        $auth = (string) Request::input('auth', '');
        if (!str_starts_with($endpoint, 'https://') || strlen($endpoint) > 500 || $p256dh === '' || strlen($p256dh) > 255 || $auth === '' || strlen($auth) > 255) {
            $this->json(['ok' => false, 'message' => 'Datos del celular inválidos.'], 422);
        }
        PushSuscripcion::guardar((int) Auth::id(), $endpoint, $p256dh, $auth);
        if (Request::isHttps()) {
            WebPushService::recordarSitio('https://' . ($_SERVER['HTTP_HOST'] ?? ''));
        }
        $this->json(['ok' => true]);
    }

    public function desuscribir(): never
    {
        PushSuscripcion::eliminarEndpoint((string) Request::input('endpoint', ''), (int) Auth::id());
        $this->json(['ok' => true]);
    }

    /** Lo pide el celular cuando le llega el aviso, para saber qué mostrar. */
    public function resumen(): never
    {
        header('Cache-Control: no-store');
        $this->json(AvisoService::resumen((int) Auth::id()));
    }

    public function probar(): never
    {
        try {
            $ok = AvisoService::tocar((int) Auth::id());
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
        $this->json($ok
            ? ['ok' => true, 'message' => 'Aviso enviado: te llega en unos segundos.']
            : ['ok' => false, 'message' => 'No se pudo enviar. Apagá y volvé a activar los avisos.']);
    }
}
