<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Local;
use App\Models\Producto;
use App\Models\ProductoLocal;

final class ProductoService
{
    /**
     * Crea o actualiza un producto.
     *
     * @param ?string $imagenNueva ruta ya guardada por ImageService (o null si no cambia)
     * @param ?array  $locales     locales marcados; null = no tocar los locales
     */
    public static function guardar(array $data, ?int $id = null, ?string $imagenNueva = null, bool $quitarImagen = false, ?array $locales = null): int
    {
        $anterior = $id !== null ? Producto::find($id)['imagen_path'] ?? null : null;

        try {
            $id = Database::transaction(function () use ($data, $id, $imagenNueva, $quitarImagen, $locales): int {
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

                if ($locales !== null) {
                    $validos = array_flip(Local::existingIds(array_keys($locales)));
                    ProductoLocal::sincronizar($id, array_intersect_key($locales, $validos));
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
