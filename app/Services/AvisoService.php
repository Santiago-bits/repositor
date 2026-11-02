<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorHandler;
use App\Models\Configuracion;
use App\Models\Fecha;
use App\Models\PushSuscripcion;
use App\Models\User;
use Throwable;

/** Avisos de fechas por vencer: a quién avisar, qué decirle y cuándo revisar. */
final class AvisoService
{
    /** Avisa a cada usuario con fechas que entraron en sus días de aviso. Cada fecha se avisa una vez. */
    public static function enviarPendientes(): array
    {
        $porUsuario = [];
        foreach (Fecha::paraAvisar() as $f) {
            $porUsuario[(int) $f['user_id']][] = (int) $f['id'];
        }

        $avisados = 0;
        foreach ($porUsuario as $userId => $ids) {
            if (self::tocar($userId)) {
                Fecha::marcarAvisadas($ids);
                $avisados++;
            }
            // Sin celular con avisos activados no se marca: le llega cuando los active.
        }
        return ['usuarios' => count($porUsuario), 'avisados' => $avisados];
    }

    /** Manda el toque a todos los celulares del usuario. true si llegó a alguno. */
    public static function tocar(int $userId): bool
    {
        $llego = false;
        foreach (PushSuscripcion::deUsuario($userId) as $s) {
            $r = WebPushService::enviar($s['endpoint']);
            if ($r === null) {
                PushSuscripcion::eliminarEndpoint($s['endpoint']);
            }
            $llego = $llego || $r === true;
        }
        return $llego;
    }

    /**
     * Revisa como mucho una vez por hora (de 8 a 21), aprovechando que alguien abre la app.
     * Así los avisos salen aunque no haya una tarea programada en el servidor.
     */
    public static function siCorresponde(): void
    {
        $hora = (int) date('G');
        if ($hora < 8 || $hora > 21) {
            return;
        }
        try {
            $ultimo = (string) Configuracion::get('avisos_ultimo', '');
            if ($ultimo !== '' && strtotime($ultimo) > time() - 3600) {
                return;
            }
            Configuracion::set('avisos_ultimo', date('Y-m-d H:i:s'));
        } catch (Throwable) {
            return; // sin base (por ejemplo, antes de migrar): ni intentar
        }
        register_shutdown_function(static function (): void {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request(); // la página ya llegó; los avisos se mandan después
            }
            try {
                self::enviarPendientes();
            } catch (Throwable $e) {
                ErrorHandler::log($e);
            }
        });
    }

    /** Lo que muestra la notificación: un resumen de lo que vence dentro de sus días de aviso. */
    public static function resumen(int $userId): array
    {
        $user = User::find($userId);
        $dias = (int) ($user['dias_aviso'] ?? 7);
        $limite = date('Y-m-d', strtotime("+{$dias} days"));
        $proximas = array_values(array_filter(Fecha::pendientes($userId, null, 7), fn ($f) => $f['fecha_vencimiento'] <= $limite));

        if ($proximas === []) {
            return ['titulo' => 'Sin fechas por vencer ✅', 'cuerpo' => "Nada vence en los próximos {$dias} días.", 'url' => url('/fechas')];
        }

        $lineas = array_map(
            fn ($f) => Fecha::nombre($f) . ' · ' . $f['local'] . ' · ' . mb_strtolower(Fecha::estado($f['fecha_vencimiento'])['etiqueta']),
            array_slice($proximas, 0, 3)
        );
        $resto = count($proximas) - count($lineas);
        if ($resto > 0) {
            $lineas[] = "y {$resto} más";
        }
        $n = count($proximas);
        return [
            'titulo' => $n === 1 ? '1 producto por vencer' : "{$n} productos por vencer",
            'cuerpo' => implode("\n", $lineas),
            'url'    => url('/fechas'),
        ];
    }
}
