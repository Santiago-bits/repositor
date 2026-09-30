<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Local;
use App\Models\User;
use App\Services\ReporteService;

/** Reportes: el admin ve todo; el repositor, solo lo suyo. */
final class ReporteController extends Controller
{
    public function index(): void
    {
        $f = $this->filtros();

        $this->view('app/reportes', [
            'title'     => 'Reportes',
            'filtros'   => $f,
            'resumen'   => ReporteService::resumen($f),
            'filas'     => array_slice(ReporteService::filas($f), 0, 200),
            'generales' => ReporteService::observacionesGenerales($f),
            'locales'   => Local::paraUsuario(auth()),
            'usuarios'  => is_admin() ? User::all() : [],
        ]);
    }

    public function csv(): never
    {
        ReporteService::descargarCsv($this->filtros());
    }

    private function filtros(): array
    {
        $desde = (string) Request::input('desde', '');
        $hasta = (string) Request::input('hasta', '');
        $desde = fecha_valida($desde) ? $desde : date('Y-m-d', strtotime('-6 days'));
        $hasta = fecha_valida($hasta) ? $hasta : date('Y-m-d');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [
            'desde'    => $desde,
            'hasta'    => $hasta,
            'local_id' => (int) Request::input('local_id', 0) ?: null,
            // El repositor no puede ver datos de otros usuarios.
            'user_id'  => is_admin() ? ((int) Request::input('user_id', 0) ?: null) : (int) auth()['id'],
        ];
    }
}
