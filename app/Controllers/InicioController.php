<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Local;
use App\Models\Configuracion;
use App\Models\Relevamiento;
use App\Models\Vencimiento;
use App\Services\DeteccionLocalService;
use App\Services\VisitaService;

final class InicioController extends Controller
{
    public function index(): void
    {
        $user = auth();
        $dias = (int) Configuracion::get('vencimiento_dias_proximo', '15');

        $this->view('app/inicio', [
            'title'   => 'Inicio',
            'user'    => $user,
            'abierta' => VisitaService::abierta((int) $user['id']),
            'hoy'     => Relevamiento::finalizadasHoy((int) $user['id']),
            'locales' => Local::paraUsuario($user),
            'cortas'  => Vencimiento::proximos($user, $dias),
            'diasCortas' => $dias,
            'scripts' => ['assets/js/inicio.js'],
        ]);
    }

    /** Recibe la ubicación del celular y devuelve el/los locales cercanos (JSON). No guarda nada. */
    public function detectar(): never
    {
        $lat = Request::input('lat');
        $lng = Request::input('lng');
        $precision = Request::input('precision', 0);

        if (!is_numeric($lat) || !is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180 || !is_numeric($precision)) {
            $this->json(['message' => 'No se pudo leer tu ubicación. Probá de nuevo.'], 422);
        }

        $this->json(DeteccionLocalService::detectar(auth(), (float) $lat, (float) $lng, (float) $precision));
    }
}
