<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Producto;

final class ProductoService
{
    /**
     * Crea o actualiza un producto.
     *
     * @param ?string $imagenNueva ruta ya guardada por ImageService (o null si no cambia)
     */
    public static function guardar(array $data, ?int $id = null, ?string $imagenNueva = null, bool $quitarImagen = false): int
    {
        $anterior = $id !== null ? Producto::find($id)['imagen_path'] ?? null : null;

        try {
            $id = Database::transaction(function () use ($data, $id, $imagenNueva, $quitarImagen): int {
                if ($id === null) {
                    $id = Producto::create($data);
                } else {
                    Producto::update($id, $data);
                }

                if ($imagenNueva !== null) {
                    Producto::setImagen($id, $imagenNueva);
                } elseif ($quitarImagen) {
                    Producto::setImagen($id, null);
                }

                return $id;
            });
        } catch (\Throwable $e) {
            ImageService::eliminar($imagenNueva); // no dejar archivos huérfanos
            throw $e;
        }

        if ($imagenNueva !== null || $quitarImagen) {
            ImageService::eliminar($anterior);
        }
        return $id;
    }
}
