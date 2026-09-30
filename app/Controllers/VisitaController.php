<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Models\Foto;
use App\Models\Observacion;
use App\Models\Promocion;
use App\Services\ConteoService;
use App\Models\Relevamiento;
use App\Models\RelevamientoProducto;
use App\Services\TareaService;
use App\Services\VisitaService;
use RuntimeException;

final class VisitaController extends VisitaBaseController
{
    public function iniciar(): void
    {
        $localId = (int) Request::input('local_id', 0);
        $lat = self::coordenada(Request::input('lat_inicio'), 90);
        $lng = self::coordenada(Request::input('lng_inicio'), 180);

        try {
            $id = VisitaService::iniciar(auth(), $localId, $lat, $lng, Request::input('cerrar_anterior') === '1');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/');
        }
        redirect('/visitas/' . $id);
    }

    public function show(int $id): void
    {
        $visita = $this->visitaVisible($id);

        $this->view('app/visitas/show', [
            'title'         => $visita['local'],
            'visita'        => $visita,
            'propia'        => (int) $visita['user_id'] === Auth::id(),
            'tareas'        => TareaService::delDia((int) $visita['local_id'], $visita['fecha']),
            'registrados'   => RelevamientoProducto::deVisita($id),
            'fotos'         => Foto::deVisita($id),
            'observaciones' => Observacion::deVisita($id),
            'promos'        => Promocion::vigentesEnLocal((int) $visita['local_id'], $visita['fecha']),
            'conteo'        => ConteoService::items($visita),
            'scripts'       => ['assets/js/visita.js'],
        ]);
    }

    public function finalizar(int $id): void
    {
        $visita = $this->visitaEditable($id);
        VisitaService::finalizar($visita);
        flash('success', 'Visita finalizada.');
        redirect('/visitas/' . $id);
    }

    /** Borra la visita con todo lo cargado. Puede hacerlo quien la hizo o el admin. */
    public function eliminar(int $id): void
    {
        $visita = $this->visitaVisible($id);
        VisitaService::eliminar($visita);
        flash('success', "Visita a {$visita['local']} eliminada.");
        redirect((int) $visita['user_id'] !== Auth::id() ? '/admin/relevamientos' : '/');
    }

    private static function coordenada(mixed $valor, int $limite): ?float
    {
        if (!is_numeric($valor) || abs((float) $valor) > $limite) {
            return null;
        }
        return round((float) $valor, 7);
    }
}
