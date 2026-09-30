<?php
declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;
use App\Models\Categoria;
use App\Models\Producto;

final class ProductoRequest
{
    public const CODIGO_REGEX = '/^[0-9A-Za-z.\-]{4,32}$/';

    /** @return array{0: array, 1: array} [datos limpios, errores] */
    public static function validate(array $input, ?int $id = null): array
    {
        $input['codigo_barras'] = preg_replace('/\s+/', '', (string) ($input['codigo_barras'] ?? ''));

        $v = new Validator($input, [
            'nombre'        => 'required|max:150',
            'marca'         => 'nullable|max:80',
            'codigo_barras' => 'nullable|max:32',
            'categoria_id'  => 'nullable|integer',
            'presentacion'  => 'nullable|max:40',
            'unidad_medida' => 'nullable|in:' . implode(',', array_keys(Producto::UNIDADES)),
            'descripcion'   => 'nullable|max:1000',
            'activo'        => 'boolean',
        ], [
            'nombre'        => 'nombre',
            'marca'         => 'marca',
            'codigo_barras' => 'El código de barras',
            'categoria_id'  => 'categoría',
            'presentacion'  => 'presentación',
            'unidad_medida' => 'unidad de medida',
            'descripcion'   => 'descripción',
        ]);

        $data = $v->validated() + ['codigo_barras' => null, 'categoria_id' => null];
        $errors = $v->errors();

        // El alta rápida desde el celular no muestra el switch: nace activo.
        if (!array_key_exists('activo', $input) && $id === null) {
            $data['activo'] = true;
        }

        $codigo = $data['codigo_barras'];
        if ($codigo !== null && !isset($errors['codigo_barras'])) {
            if (!preg_match(self::CODIGO_REGEX, $codigo)) {
                $errors['codigo_barras'] = 'El código solo puede tener números, letras, puntos o guiones (4 a 32).';
            } elseif (Producto::codigoExiste($codigo, $id)) {
                $errors['codigo_barras'] = 'Ya hay un producto con ese código de barras.';
            }
        }

        if ($data['categoria_id'] !== null && !isset($errors['categoria_id']) && Categoria::find($data['categoria_id']) === null) {
            $errors['categoria_id'] = 'Elegí una categoría válida.';
        }

        if (!isset($errors['nombre'])) {
            $dup = Producto::duplicado($data['nombre'], $data['marca'] ?? null, $data['presentacion'] ?? null, $id);
            if ($dup !== null) {
                $errors['nombre'] = 'Este producto ya existe (' . Producto::nombreCompleto($dup) . '). No hace falta cargarlo de nuevo.';
            }
        }

        return [$data, $errors];
    }

}
