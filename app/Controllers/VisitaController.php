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
use App\Models\Vencimiento;
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
            $id = VisitaService::iniciar(auth(), $localId, $lat, $lng);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/');
        }
        redirect('/visitas/' . $id);
    }

    public function show(int $id): void
    {
        $visita = $this->visitaVisible($id);
        $promos = Promocion::vigentesEnLocal((int) $visita['local_id'], $visita['fecha']);

        // Lotes que siguen en el local (los retirados ya no cuentan), agrupados por producto.
        $lotes = [];
        foreach (Vencimiento::deVisita($id) as $v) {
            if ($v['retirado_at'] === null) {
                $lotes[(int) $v['producto_id']][] = $v;
            }
        }

        // Solo lo que quedó anotado: si se sacó el faltante, el vencimiento o la promo, el producto no se muestra.
        $registrados = array_values(array_filter(
            RelevamientoProducto::deVisita($id),
            fn ($r) => $r['stock'] !== null
                || in_array($r['estado_stock'], ['bajo', 'sin_stock', 'no_exhibido'], true)
                || $r['con_problema']
                || isset($lotes[(int) $r['producto_id']])
                || isset($promos[(int) $r['producto_id']])
        ));

        $this->view('app/visitas/show', [
            'title'         => $visita['local'],
            'visita'        => $visita,
            'propia'        => (int) $visita['user_id'] === Auth::id(),
            'registrados'   => $registrados,
            'lotes'         => $lotes,
            'fotos'         => Foto::deVisita($id),
            'observaciones' => Observacion::deVisita($id),
            'promos'        => $promos,
            'conteo'        => ConteoService::items($visita),
            // Para acomodar la heladera: lo que vence primero en este local (próximos 60 días y vencidos recientes).
            'cortasLocal'   => array_values(array_filter(
                Vencimiento::proximos(auth(), 60),
                fn ($v) => (int) $v['local_id'] === (int) $visita['local_id']
            )),
            'scripts'       => ['assets/js/visita.js'],
        ]);
    }

    /** Cierre sin preguntar: lo usa el GPS cuando detecta que te fuiste del local. */
    public function finalizar(int $id): void
    {
        $visita = $this->visitaVisible($id);
        if ((int) $visita['user_id'] === Auth::id() && $visita['estado'] === 'en_proceso') {
            VisitaService::finalizar($visita);
        }
        $this->responder('Visita cerrada.', [], '/');
    }

    /** Borra la visita con todo lo cargado. Puede hacerlo quien la hizo o el admin. */
    public function eliminar(int $id): void
    {
        $visita = $this->visitaVisible($id);
        VisitaService::eliminar($visita);
        flash('success', "Visita a {$visita['local']} eliminada.");
        redirect('/');
    }

    private static function coordenada(mixed $valor, int $limite): ?float
    {
        if (!is_numeric($valor) || abs((float) $valor) > $limite) {
            return null;
        }
        return round((float) $valor, 7);
    }
}
