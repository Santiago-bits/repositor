<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Models\Observacion;
use App\Models\Producto;

final class ObservacionController extends VisitaBaseController
{
    public function crear(int $id): never
    {
        $this->visitaEditable($id);

        $texto = (string) Request::input('texto', '');
        if (!mb_check_encoding($texto, 'UTF-8')) {
            $this->fallar('El texto tiene caracteres no válidos. Escribilo de nuevo.');
        }
        $texto = trim((string) preg_replace('/\s+/u', ' ', $texto));
        if ($texto === '') {
            $this->fallar('Escribí la observación o elegí una frase.');
        }
        if (mb_strlen($texto) > 500) {
            $this->fallar('La observación no puede superar los 500 caracteres.');
        }

        $productoId = (int) Request::input('producto_id', 0) ?: null;
        if ($productoId !== null && Producto::find($productoId) === null) {
            $this->fallar('Ese producto no existe.');
        }

        Observacion::crear($id, $productoId, $texto);
        $this->responder('Observación guardada', ['html' => $this->htmlObservaciones($id, $productoId)], "/visitas/{$id}#observaciones");
    }

    public function eliminar(int $observacionId): never
    {
        $observacion = $this->notFoundUnless(Observacion::find($observacionId));
        $this->visitaEditable((int) $observacion['relevamiento_id']);

        Observacion::eliminar($observacionId);
        $productoId = (int) Request::input('producto_id', 0) ?: null;
        $this->responder('Observación eliminada', ['html' => $this->htmlObservaciones((int) $observacion['relevamiento_id'], $productoId)]);
    }

    private function htmlObservaciones(int $visitaId, ?int $productoId): string
    {
        $alcance = Request::input('scope') === 'producto' ? $productoId : null;
        return View::partial('observaciones-lista', [
            'observaciones'   => Observacion::deVisita($visitaId, $alcance),
            'editable'        => true,
            'mostrarProducto' => $alcance === null,
            'productoId'      => $alcance,
        ]);
    }
}
