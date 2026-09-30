<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Models\Local;
use App\Models\Producto;
use App\Models\ProductoLocal;
use App\Models\Promocion;
use App\Models\Tarea;
use App\Requests\PromocionRequest;
use App\Services\ConteoService;
use App\Services\PromocionService;
use App\Services\TareaService;

/** Dentro de una visita: registrar promociones vistas en góndola, conteo de promociones y tareas. */
final class VisitaPromocionController extends VisitaBaseController
{
    public function crear(int $id): void
    {
        $visita = $this->visitaEditable($id);
        $user = auth();

        $this->view('app/visitas/promocion', [
            'title'     => 'Registrar promoción',
            'visita'    => $visita,
            'promo'     => [
                'producto_id'  => (int) Request::input('producto', 0) ?: null,
                'fecha_inicio' => date('Y-m-d'),
                'fecha_fin'    => PromocionService::finPorDefecto(),
            ],
            'grupos'    => self::opcionesProducto($visita),
            'atajos'    => PromocionService::atajosFin(),
            'otros'     => array_filter(Local::paraUsuario($user), fn ($l) => (int) $l['id'] !== (int) $visita['local_id']),
            'scripts'   => ['assets/js/escaner.js', 'assets/js/visita.js'],
        ]);
    }

    public function guardar(int $id): void
    {
        $visita = $this->visitaEditable($id);
        $user = auth();
        [$data, $errors] = PromocionRequest::validate($_POST);
        $data['estado'] = 'activa';

        // Siempre el local de la visita; los demás, solo si el usuario puede trabajarlos.
        $locales = [(int) $visita['local_id']];
        foreach ($data['locales'] as $localId) {
            if (Local::accesible($user, $localId)) {
                $locales[] = $localId;
            }
        }
        $locales = array_values(array_unique($locales));

        if (!$errors) {
            $solapada = Promocion::solapada($data['producto_id'], $locales, $data['fecha_inicio'], $data['fecha_fin']);
            if ($solapada) {
                $errors['producto_id'] = "Ya hay una promo de ese producto en {$solapada['local']} del "
                    . fecha($solapada['fecha_inicio'], 'd/m') . ' al ' . fecha($solapada['fecha_fin'], 'd/m') . '.';
            }
        }
        if ($errors) {
            $this->backWithErrors($errors, $_POST, "/visitas/{$id}/promociones/crear");
        }

        PromocionService::guardar($data, $locales, (int) $user['id']);
        flash('success', 'Promoción registrada hasta el ' . fecha($data['fecha_fin'], 'd/m') . '.');
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
