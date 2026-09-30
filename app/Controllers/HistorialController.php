<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Relevamiento;
use App\Services\MensajeService;

/** Historial de visitas y mensaje para el supervisor. */
final class HistorialController extends VisitaBaseController
{
    public function index(): void
    {
        $dias = in_array((int) Request::input('dias', 30), [30, 90, 365], true) ? (int) Request::input('dias', 30) : 30;
        $visitas = Relevamiento::historial((int) auth()['id'], date('Y-m-d', strtotime("-{$dias} days")));

        $porFecha = [];
        foreach ($visitas as $v) {
            $porFecha[$v['fecha']][] = $v;
        }

        $this->view('app/historial', ['title' => 'Historial', 'porFecha' => $porFecha, 'dias' => $dias]);
    }

    /** Mensaje de todas las visitas del día (por defecto, hoy). */
    public function mensajeDelDia(): void
    {
        $fecha = (string) Request::input('fecha', date('Y-m-d'));
        $fecha = fecha_valida($fecha) ? $fecha : date('Y-m-d');
        $tipo = Request::input('tipo') === 'promos' ? 'promos' : 'completo';

        $this->view('app/mensaje', [
            'title'   => 'Mensaje para el supervisor',
            'mensaje' => MensajeService::delDia((int) auth()['id'], $fecha, $tipo),
            'fecha'   => $fecha,
            'tipo'    => $tipo,
            'visita'  => null,
        ]);
    }

    /** Mensaje de una sola visita. */
    public function mensajeDeVisita(int $id): void
    {
        $visita = $this->visitaVisible($id);
        $tipo = Request::input('tipo') === 'promos' ? 'promos' : 'completo';

        $this->view('app/mensaje', [
            'title'   => 'Reporte de la visita',
            'mensaje' => MensajeService::deVisita($visita, $tipo),
            'fecha'   => $visita['fecha'],
            'tipo'    => $tipo,
            'visita'  => $visita,
        ]);
    }
}
