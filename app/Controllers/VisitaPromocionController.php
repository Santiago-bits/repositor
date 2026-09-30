<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\View;
use App\Models\Producto;
use App\Models\ProductoLocal;
use App\Models\Promocion;
use App\Models\RelevamientoProducto;
use App\Models\Tarea;
use App\Services\ConteoService;
use App\Services\PromocionService;
use App\Services\RegistroVisitaService;
use App\Services\TareaService;

/** Dentro de una visita: registrar promociones vistas en góndola, conteo de promociones y tareas. */
final class VisitaPromocionController extends VisitaBaseController
{
    /** Promos del finde: lista de productos del local para marcar (sin fechas). */
    public function crear(int $id): void
    {
        $visita = $this->visitaEditable($id);

        $this->view('app/visitas/promocion', [
            'title'     => 'Promos del finde',
            'visita'    => $visita,
            'productos' => ProductoLocal::productosDeLocal((int) $visita['local_id'], $id),
            'enPromo'   => Promocion::vigentesEnLocal((int) $visita['local_id'], $visita['fecha']),
            'marcado'   => (int) Request::input('producto', 0),
            'scripts'   => ['assets/js/visita.js'],
        ]);
    }

    /**
     * Guarda las promos marcadas. Las fechas no se piden: van de hoy al lunes,
     * así el lunes aparecen solas en el conteo. Desmarcar una promo propia la borra.
     */
    public function guardar(int $id): void
    {
        $visita = $this->visitaEditable($id);
        $userId = (int) auth()['id'];
        $localId = (int) $visita['local_id'];
        $marcados = array_map('intval', array_keys((array) Request::input('promo', [])));
        $stocks = (array) Request::input('stock', []);
        $guardadas = 0;

        Database::transaction(function () use ($visita, $id, $userId, $localId, $marcados, $stocks, &$guardadas): void {
            $enPromo = Promocion::vigentesEnLocal($localId, $visita['fecha']);

            foreach (ProductoLocal::productosDeLocal($localId, $id) as $p) {
                $pid = (int) $p['id'];
                $promo = $enPromo[$pid] ?? null;

                if (!in_array($pid, $marcados, true)) {
                    if ($promo && (int) $promo['created_by'] === $userId) {
                        Promocion::eliminar((int) $promo['id']);
                    }
                    continue;
                }

                $promoId = $promo ? (int) $promo['id'] : Promocion::create([
                    'producto_id'   => $pid,
                    'fecha_inicio'  => $visita['fecha'],
                    'fecha_fin'     => max($visita['fecha'], PromocionService::finPorDefecto()),
                    'precio_normal' => null,
                    'precio_promo'  => null,
                    'observaciones' => null,
                ], $userId);
                if (!$promo) {
                    Promocion::syncLocales($promoId, [$localId]);
                }

                $stock = filter_var(trim((string) ($stocks[$pid] ?? '')), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999]]);
                if ($stock !== false) {
                    RelevamientoProducto::guardarStock($id, $pid, $stock, RegistroVisitaService::estadoPorDefecto($stock), false, $promoId);
                }
                $guardadas++;
            }
        });

        flash('success', $guardadas === 1 ? '1 promo guardada.' : "{$guardadas} promos guardadas.");
        redirect(Request::input('mensaje') === '1'
            ? '/mensaje?fecha=' . $visita['fecha'] . '&tipo=promos'
            : "/visitas/{$id}#promociones");
    }

    /** Borra una promo desde la visita (la que cargó el usuario, o cualquiera si es admin). */
    public function eliminar(int $id, int $promoId): void
    {
        $this->visitaEditable($id);
        $promo = $this->notFoundUnless(Promocion::find($promoId));
        if ((int) $promo['created_by'] !== (int) auth()['id'] && !is_admin()) {
            View::error(403);
        }
        Promocion::eliminar($promoId);
        flash('success', 'Promoción borrada.');
        redirect("/visitas/{$id}#promociones");
    }

    /** Conteo de a un producto por vez: muestra el primero pendiente (o el elegido con ?promo=). */
    public function conteo(int $id): void
    {
        $visita = $this->visitaEditable($id);
        $items = ConteoService::items($visita);
        $elegido = (int) Request::input('promo', 0);

        $actual = null;
        foreach ($items as $i => $item) {
            if ($elegido ? (int) $item['promo_id'] === $elegido : $item['rp_id'] === null) {
                $actual = $item + ['posicion' => $i + 1];
                break;
            }
        }

        $this->view('app/visitas/conteo', [
            'title'    => 'Conteo de promociones',
            'visita'   => $visita,
            'items'    => $items,
            'actual'   => $actual,
            'contados' => count(array_filter($items, fn ($i) => $i['rp_id'] !== null)),
            'scripts'  => ['assets/js/visita.js'],
        ]);
    }

    public function guardarConteo(int $id, int $promoId): void
    {
        $visita = $this->visitaEditable($id);
        $item = null;
        foreach (ConteoService::items($visita) as $i) {
            if ((int) $i['promo_id'] === $promoId) {
                $item = $i;
            }
        }
        if ($item === null) {
            $this->fallar('Esa promoción no está para contar en este local.', 404, "/visitas/{$id}/conteo");
        }

        $stock = filter_var(Request::input('stock'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999]]);
        if ($stock === false) {
            $this->fallar('Ingresá el stock encontrado (un número).', 422, "/visitas/{$id}/conteo?promo={$promoId}");
        }

        ConteoService::guardar($visita, $item, $stock, Request::input('no_exhibido') === '1');
        flash('success', '✅ ' . Producto::nombreCompleto($item) . " registrado: {$stock} u.");
        redirect("/visitas/{$id}/conteo");
    }

    /** Marca o desmarca una tarea del día como hecha. */
    public function tarea(int $id, int $tareaId): never
    {
        $visita = $this->visitaEditable($id);
        $tareas = TareaService::delDia((int) $visita['local_id'], $visita['fecha']);
        $tarea = null;
        foreach ($tareas as $t) {
            if ((int) $t['id'] === $tareaId) {
                $tarea = $t;
            }
        }
        if ($tarea === null) {
            $this->fallar('Esa tarea no corresponde a este local hoy.', 404);
        }

        if ($tarea['hecha']) {
            Tarea::desmarcar($tareaId, (int) $visita['local_id'], $visita['fecha']);
        } else {
            Tarea::marcar($tareaId, (int) $visita['local_id'], $id, (int) $visita['user_id'], $visita['fecha']);
        }

        $this->responder($tarea['hecha'] ? 'Tarea pendiente' : '✅ Tarea hecha', [
            'html' => View::partial('tareas-visita', [
                'visita'    => $visita,
                'tareas'    => TareaService::delDia((int) $visita['local_id'], $visita['fecha']),
                'editable'  => true,
                'pendientes' => count(array_filter(ConteoService::items($visita), fn ($i) => $i['rp_id'] === null)),
            ]),
        ], "/visitas/{$id}");
    }

    /** Productos del local primero; después el resto del catálogo. */
    private static function opcionesProducto(array $visita): array
    {
        $delLocal = [];
        foreach (ProductoLocal::productosDeLocal((int) $visita['local_id'], (int) $visita['id']) as $p) {
            $delLocal[(int) $p['id']] = Producto::nombreCompleto($p) . ($p['marca'] ? " · {$p['marca']}" : '');
        }
        $otros = [];
        foreach (Producto::todos() as $p) {
            if ($p['activo'] && !isset($delLocal[(int) $p['id']])) {
                $otros[(int) $p['id']] = Producto::nombreCompleto($p) . ($p['marca'] ? " · {$p['marca']}" : '');
            }
        }
        return array_filter(['Productos del local' => $delLocal, 'Otros productos' => $otros]);
    }
}
