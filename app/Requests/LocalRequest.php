<?php
declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;
use App\Models\Local;

final class LocalRequest
{
    /** @return array{0: array, 1: array} [datos limpios, errores] */
    public static function validate(array $input): array
    {
        $input = self::normalizarCoordenadas($input);

        $v = new Validator($input, [
            'nombre'        => 'required|max:120',
            'tipo'          => 'required|in:' . implode(',', array_keys(Local::TIPOS)),
            'direccion'     => 'nullable|max:200',
            'latitud'       => 'nullable|numeric|between:-90,90',
            'longitud'      => 'nullable|numeric|between:-180,180',
            'radio_m'       => 'required|integer|between:10,2000',
            'telefono'      => 'nullable|max:40',
            'contacto'      => 'nullable|max:120',
            'observaciones' => 'nullable|max:1000',
            'activo'        => 'boolean',
            'es_prueba'     => 'boolean',
        ], [
            'nombre'        => 'nombre',
            'tipo'          => 'tipo de comercio',
            'direccion'     => 'dirección',
            'latitud'       => 'La latitud',
            'longitud'      => 'La longitud',
            'radio_m'       => 'El radio',
            'telefono'      => 'teléfono',
            'contacto'      => 'contacto',
            'observaciones' => 'observaciones',
        ]);

        $data = $v->validated();
        $errors = $v->errors();

        $conLat = ($data['latitud'] ?? null) !== null;
        $conLng = ($data['longitud'] ?? null) !== null;
        if ($conLat !== $conLng && !isset($errors['latitud']) && !isset($errors['longitud'])) {
            $errors[$conLat ? 'longitud' : 'latitud'] = 'Cargá latitud y longitud juntas (o dejá las dos vacías).';
        }

        return [$data, $errors];
    }

    /**
     * Acepta coma decimal ("-31,39") y el par que copia Google Maps en el campo latitud
     * ("-31.3929, -58.0209"), así se puede pegar directo.
     */
    private static function normalizarCoordenadas(array $input): array
    {
        $lat = trim((string) ($input['latitud'] ?? ''));
        $lng = trim((string) ($input['longitud'] ?? ''));

        if ($lng === '' && preg_match('/^(-?\d+\.\d+)\s*[,;\s]\s*(-?\d+\.\d+)$/', $lat, $m)) {
            [$lat, $lng] = [$m[1], $m[2]];
        }

        $input['latitud'] = str_replace(',', '.', $lat);
        $input['longitud'] = str_replace(',', '.', $lng);
        return $input;
    }
}
