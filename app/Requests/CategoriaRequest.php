<?php
declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;
use App\Models\Categoria;

final class CategoriaRequest
{
    /** @return array{0: array, 1: array} [datos limpios, errores] */
    public static function validate(array $input, ?int $id = null): array
    {
        $v = new Validator($input, [
            'nombre'    => 'required|max:80',
            'parent_id' => 'nullable|integer',
            'activo'    => 'boolean',
        ], ['nombre' => 'nombre', 'parent_id' => 'categoría principal']);

        $data = $v->validated();
        $errors = $v->errors();

        // En el alta rápida el switch no se muestra: nace activa.
        if (!array_key_exists('activo', $input) && $id === null) {
            $data['activo'] = true;
        }

        $parentId = $data['parent_id'] ?? null;
        if ($parentId !== null && !isset($errors['parent_id'])) {
            $padre = Categoria::find($parentId);
            if ($padre === null || $padre['parent_id'] !== null) {
                $errors['parent_id'] = 'Elegí una categoría principal válida.';
            } elseif ($parentId === $id) {
                $errors['parent_id'] = 'Una categoría no puede ser subcategoría de sí misma.';
            } elseif ($id !== null && Categoria::tieneHijas($id)) {
                $errors['parent_id'] = 'Esta categoría tiene subcategorías: no puede pasar a ser subcategoría.';
            }
        }

        if (!isset($errors['nombre']) && Categoria::existeNombre($data['nombre'], $parentId, $id)) {
            $errors['nombre'] = 'Ya existe una categoría con ese nombre en ese nivel.';
        }

        return [$data, $errors];
    }
}
