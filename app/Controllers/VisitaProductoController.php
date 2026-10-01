<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Models\Foto;
use App\Models\Observacion;
use App\Models\Producto;
use App\Models\ProductoLocal;
use App\Models\Promocion;
use App\Models\RelevamientoProducto;
use App\Models\Vencimiento;
use App\Services\RegistroVisitaService;

/** Un producto dentro de una visita: hay / poco / sin stock y sus vencimientos. */
final class VisitaProductoController extends VisitaBaseController
{
    public function show(int $id, int $productoId): void
    {
        $visita = $this->visitaEditable($id);
        $producto = $this->productoActivo($productoId);
        $rp = RelevamientoProducto::find($id, $productoId);

        $this->view('app/visitas/producto', [
            'title'           => Producto::nombreCompleto($producto),
            'visita'          => $visita,
            'producto'        => $producto,
            'rp'              => $rp,
            'anterior'        => RelevamientoProducto::anteriorEnLocal((int) $visita['local_id'], $productoId, $id),
            'ubicacion'       => ProductoLocal::ubicacion($productoId, (int) $visita['local_id']),
            'vencimientos'    => $rp ? Vencimiento::deRegistro((int) $rp['id']) : [],
            'lotesAnteriores' => RegistroVisitaService::lotesAnteriores($visita, $productoId),
            'fotos'           => Foto::deVisita($id, $productoId),
            'observaciones'   => Observacion::deVisita($id, $productoId),
            'promo'           => Promocion::vigentesEnLocal((int) $visita['local_id'], $visita['fecha'])[$productoId] ?? null,
            'scripts'         => ['assets/js/visita.js'],
        ]);
    }

    public function guardarStock(int $id, int $productoId): never
    {
        $visita = $this->visitaEditable($id);
        $this->productoActivo($productoId);

        $texto = trim((string) Request::input('stock', ''));
        $stock = null;
        if ($texto !== '') {
            $stock = filter_var($texto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999]]);
            if ($stock === false) {
                $this->fallar('El stock tiene que ser un número entero entre 0 y 99999.');
            }
        }

        $estado = (string) Request::input('estado_stock', '');
        if ($estado !== '' && !isset(RelevamientoProducto::ESTADOS[$estado])) {
            $this->fallar('Elegí un estado válido.');
        }
        $estado = $estado !== '' ? $estado : RegistroVisitaService::estadoPorDefecto($stock);
        if ($stock === null && $estado === null) {
            $this->fallar('Ingresá el stock encontrado o elegí un estado.');
        }

        RegistroVisitaService::guardarStock($visita, $productoId, $stock, $estado, Request::input('con_problema') === '1');

        $this->responder('Guardado', ['estado' => $estado, 'hora' => date('H:i')], "/visitas/{$id}/productos/{$productoId}");
    }

    public function agregarVencimiento(int $id, int $productoId): never
    {
        $visita = $this->visitaEditable($id);
        $this->productoActivo($productoId);

        $fecha = (string) Request::input('fecha', '');
        $d = \DateTime::createFromFormat('!Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha || $fecha < '2000-01-01' || $fecha > date('Y-m-d', strtotime('+15 years'))) {
            $this->fallar('Ingresá una fecha de vencimiento válida.');
        }

        $texto = trim((string) Request::input('cantidad', ''));
        $cantidad = null;
        if ($texto !== '') {
            $cantidad = filter_var($texto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999]]);
            if ($cantidad === false) {
                $this->fallar('La cantidad tiene que ser un número entero.');
            }
        }

        $nota = trim((string) Request::input('nota', ''));
        $nota = $nota === '' || !mb_check_encoding($nota, 'UTF-8') ? null : mb_substr($nota, 0, 120);

        RegistroVisitaService::agregarVencimiento($visita, $productoId, $fecha, $cantidad, $nota);
        $this->responder('Vencimiento agregado', ['html' => $this->htmlVencimientos($id, $productoId)]);
    }

    public function copiarVencimientos(int $id, int $productoId): never
    {
        $visita = $this->visitaEditable($id);
        $this->productoActivo($productoId);

        $copiados = RegistroVisitaService::copiarLotesAnteriores($visita, $productoId);
        $mensaje = match ($copiados) {
            0       => 'No había lotes nuevos para copiar.',
            1       => 'Se copió 1 lote.',
            default => "Se copiaron {$copiados} lotes.",
        };
        $this->responder($mensaje, ['html' => $this->htmlVencimientos($id, $productoId)]);
    }

    public function eliminarVencimiento(int $id, int $vencimientoId): never
    {
        $this->visitaEditable($id);
        $vencimiento = $this->notFoundUnless(Vencimiento::findEnVisita($vencimientoId, $id));

        Vencimiento::eliminar($vencimientoId);
        $this->responder('Vencimiento eliminado', ['html' => $this->htmlVencimientos($id, (int) $vencimiento['producto_id'])]);
    }

    /** Saca un producto de la visita (por si se cargó por error). */
    public function quitar(int $id, int $productoId): never
    {
        $this->visitaEditable($id);
        RelevamientoProducto::quitar($id, $productoId);
        $this->responder('Producto quitado de la visita.', [], "/visitas/{$id}");
    }

    private function productoActivo(int $productoId): array
    {
        $producto = Producto::find($productoId);
        if ($producto === null || !$producto['activo']) {
            $this->fallar('Ese producto no existe o está desactivado.', 404);
        }
        return $producto;
    }

    private function htmlVencimientos(int $visitaId, int $productoId): string
    {
        $rp = RelevamientoProducto::find($visitaId, $productoId);
        return View::partial('vencimientos-lista', [
            'vencimientos' => $rp ? Vencimiento::deRegistro((int) $rp['id']) : [],
            'visitaId'     => $visitaId,
            'editable'     => true,
        ]);
    }
}
