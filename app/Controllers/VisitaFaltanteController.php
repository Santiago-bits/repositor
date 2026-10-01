<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\ProductoLocal;
use App\Models\RelevamientoProducto;

/** Faltantes del local: lo que no hay o hay poco, para pasarle la lista al vendedor. Sin contar unidades. */
final class VisitaFaltanteController extends VisitaBaseController
{
    private const ESTADOS = ['sin_stock', 'bajo'];

    public function crear(int $id): void
    {
        $visita = $this->visitaEditable($id);

        $this->view('app/visitas/faltantes', [
            'title'     => 'Faltantes',
            'visita'    => $visita,
            'productos' => ProductoLocal::productosDeLocal((int) $visita['local_id'], $id),
            'faltantes' => RelevamientoProducto::faltantesDeVisita($id),
            'scripts'   => ['assets/js/visita.js'],
        ]);
    }

    /** Entrada: falta[producto_id] = sin_stock | bajo. Lo que estaba marcado y ya no viene, se desmarca. */
    public function guardar(int $id): void
    {
        $visita = $this->visitaEditable($id);

        $validos = array_flip(array_map(
            'intval',
            array_column(ProductoLocal::productosDeLocal((int) $visita['local_id'], $id), 'id')
        ));

        $marcados = [];
        foreach ((array) ($_POST['falta'] ?? []) as $productoId => $estado) {
            $productoId = (int) $productoId;
            if (isset($validos[$productoId]) && in_array($estado, self::ESTADOS, true)) {
                $marcados[$productoId] = $estado;
            }
        }

        Database::transaction(function () use ($id, $marcados): void {
            foreach (RelevamientoProducto::faltantesDeVisita($id) as $antes) {
                if (!isset($marcados[(int) $antes['producto_id']])) {
                    RelevamientoProducto::desmarcarFaltante($id, (int) $antes['producto_id']);
                }
            }
            foreach ($marcados as $productoId => $estado) {
                RelevamientoProducto::marcarFaltante($id, $productoId, $estado);
            }
        });

        $n = count($marcados);
        flash('success', $n === 0 ? 'No quedan faltantes en este local.' : ($n === 1 ? '1 faltante guardado.' : "{$n} faltantes guardados."));
        redirect(($_POST['mensaje'] ?? '') === '1'
            ? "/visitas/{$id}/mensaje?tipo=faltantes"
            : "/visitas/{$id}/faltantes");
    }
}
