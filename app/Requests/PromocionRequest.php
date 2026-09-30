<?php
declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;
use App\Models\Producto;
use App\Models\Promocion;

final class PromocionRequest
{
    /** @return array{0: array, 1: array} [datos limpios, errores] */
    public static function validate(array $input): array
    {
        foreach (['precio_normal', 'precio_promo'] as $campo) {
            $input[$campo] = self::precio((string) ($input[$campo] ?? ''));
        }

        $v = new Validator($input, [
            'producto_id'   => 'required|integer',
            'fecha_inicio'  => 'required',
            'fecha_fin'     => 'required',
            'precio_normal' => 'nullable|numeric|between:0,99999999',
            'precio_promo'  => 'nullable|numeric|between:0,99999999',
            'observaciones' => 'nullable|max:255',
            'estado'        => 'nullable|in:' . implode(',', array_keys(Promocion::ESTADOS)),
        ], [
            'producto_id'   => 'producto',
            'fecha_inicio'  => 'desde',
            'fecha_fin'     => 'hasta',
            'precio_normal' => 'El precio normal',
            'precio_promo'  => 'El precio promocional',
            'observaciones' => 'observaciones',
            'estado'        => 'estado',
        ]);

        $data = $v->validated();
        $errors = $v->errors();

        if (!isset($errors['producto_id'])) {
            $producto = Producto::find($data['producto_id']);
            if ($producto === null || !$producto['activo']) {
                $errors['producto_id'] = 'Elegí un producto válido.';
            }
        }

        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            if (!isset($errors[$campo]) && !fecha_valida((string) $data[$campo])) {
                $errors[$campo] = 'Ingresá una fecha válida.';
            }
        }
        if (!isset($errors['fecha_inicio']) && !isset($errors['fecha_fin'])) {
            if ($data['fecha_fin'] < $data['fecha_inicio']) {
                $errors['fecha_fin'] = 'La fecha "hasta" no puede ser anterior a "desde".';
            } elseif (strtotime($data['fecha_fin']) - strtotime($data['fecha_inicio']) > 366 * 86400) {
                $errors['fecha_fin'] = 'Una promoción no puede durar más de un año.';
            }
        }

        $data['estado'] ??= 'activa';
        $data['locales'] = array_values(array_unique(array_filter(
            array_map('intval', (array) ($input['locales'] ?? []))
        )));

        return [$data, $errors];
    }

    /** Acepta "5200", "5.200", "5200,50" y "5.200,50". */
    private static function precio(string $valor): string
    {
        $valor = trim(str_replace(['$', ' '], '', $valor));
        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $valor)) {
            $valor = str_replace('.', '', $valor);
        }
        return $valor;
    }
}
