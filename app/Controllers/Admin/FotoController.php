<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Foto;
use App\Models\Local;

/** Galería de todas las fotos de relevamientos. */
final class FotoController extends Controller
{
    public function index(): void
    {
        $desde = (string) Request::input('desde', '');
        $hasta = (string) Request::input('hasta', '');
        $filtros = [
            'desde'    => fecha_valida($desde) ? $desde : date('Y-m-d', strtotime('-6 days')),
            'hasta'    => fecha_valida($hasta) ? $hasta : date('Y-m-d'),
            'local_id' => (int) Request::input('local_id', 0) ?: null,
        ];

        $this->view('admin/fotos', [
            'title'   => 'Fotos',
            'filtros' => $filtros,
            'fotos'   => Foto::buscar($filtros),
            'locales' => Local::all(),
        ]);
    }
}
