<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Local;
use App\Models\Relevamiento;
use App\Models\User;

final class RelevamientoController extends Controller
{
    public function index(): void
    {
        $desde = self::fecha(Request::input('desde')) ?? date('Y-m-d', strtotime('-6 days'));
        $hasta = self::fecha(Request::input('hasta')) ?? date('Y-m-d');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $estado = (string) Request::input('estado', '');
        $filtros = [
            'desde'    => $desde,
            'hasta'    => $hasta,
            'local_id' => (int) Request::input('local_id', 0) ?: null,
            'user_id'  => (int) Request::input('user_id', 0) ?: null,
            'estado'   => isset(Relevamiento::ESTADOS[$estado]) ? $estado : null,
        ];

        $this->view('admin/relevamientos/index', [
            'title'          => 'Relevamientos',
            'filtros'        => $filtros,
            'relevamientos'  => Relevamiento::buscar($filtros),
            'locales'        => Local::all(),
            'usuarios'       => User::all(),
        ]);
    }

    private static function fecha(mixed $valor): ?string
    {
        $d = \DateTime::createFromFormat('!Y-m-d', (string) $valor);
        return $d && $d->format('Y-m-d') === $valor ? $valor : null;
    }
}
