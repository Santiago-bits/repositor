<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Models\Foto;
use App\Models\Producto;
use App\Services\ImageService;
use RuntimeException;
use Throwable;

final class FotoController extends VisitaBaseController
{
    public function subir(int $id): never
    {
        $visita = $this->visitaEditable($id);

        $productoId = (int) Request::input('producto_id', 0) ?: null;
        if ($productoId !== null && Producto::find($productoId) === null) {
            $this->fallar('Ese producto no existe.');
        }
        if (ImageService::sinArchivo($_FILES['foto'] ?? null)) {
            $this->fallar('Elegí o sacá una foto.');
        }

        $carpeta = 'local_' . $visita['local_id'] . ($productoId ? '/producto_' . $productoId : '');
        try {
            $info = ImageService::procesar($_FILES['foto'], 'relevamientos', 1600, 80, $carpeta);
        } catch (RuntimeException $e) {
            $this->fallar($e->getMessage());
        }

        try {
            Foto::crear($id, $productoId, $info);
        } catch (Throwable $e) {
            ImageService::eliminar($info['path']);
            throw $e;
        }

        $this->responder('Foto guardada', ['html' => $this->htmlFotos($id, $productoId)], "/visitas/{$id}");
    }

    /** Sirve la imagen solo a quien hizo la visita o al admin. */
    public function ver(int $fotoId): never
    {
        $foto = $this->notFoundUnless(Foto::find($fotoId));
        if ((int) $foto['user_id'] !== Auth::id() && !is_admin()) {
            View::error(403);
        }
        ImageService::enviar($foto['path']);
    }

    public function eliminar(int $fotoId): never
    {
        $foto = $this->notFoundUnless(Foto::find($fotoId));
        $this->visitaEditable((int) $foto['relevamiento_id']);

        Foto::eliminar($fotoId);
        ImageService::eliminar($foto['path']);

        $productoId = (int) Request::input('producto_id', 0) ?: null;
        $this->responder('Foto eliminada', ['html' => $this->htmlFotos((int) $foto['relevamiento_id'], $productoId)]);
    }

    /** Con producto: solo sus fotos; sin producto: todas las de la visita. */
    private function htmlFotos(int $visitaId, ?int $productoId): string
    {
        $alcance = Request::input('scope') === 'producto' ? $productoId : null;
        return View::partial('fotos-grid', [
            'fotos'           => Foto::deVisita($visitaId, $alcance),
            'editable'        => true,
            'mostrarProducto' => $alcance === null,
            'productoId'      => $alcance,
        ]);
    }
}
