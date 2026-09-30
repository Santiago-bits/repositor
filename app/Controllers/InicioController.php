<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Local;
use App\Models\Relevamiento;
use App\Services\DeteccionLocalService;

final class InicioController extends Controller
{
    public function index(): void
    {
        $user = auth();

        $this->view('app/inicio', [
            'title'   => 'Inicio',
            'user'    => $user,
            'abierta' => Relevamiento::abiertaDeUsuario((int) $user['id']),
            'hoy'     => Relevamiento::finalizadasHoy((int) $user['id']),
            'locales' => Local::paraUsuario($user),
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
