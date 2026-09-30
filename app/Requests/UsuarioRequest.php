<?php
declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;
use App\Models\Role;
use App\Models\User;

final class UsuarioRequest
{
    /** @return array{0: array, 1: array} [datos limpios, errores] */
    public static function validate(array $input, ?int $id = null): array
    {
        $v = new Validator($input, [
            'nombre'   => 'required|max:80',
            'apellido' => 'required|max:80',
            'email'    => 'required|email|max:150',
            'role_id'  => 'required|integer',
            'password' => ($id ? 'nullable' : 'required') . '|min:8|max:72',
            'activo'   => 'boolean',
        ], [
            'nombre'   => 'nombre',
            'apellido' => 'apellido',
            'email'    => 'email',
            'role_id'  => 'rol',
            'password' => 'La contraseña',
        ]);

        $data = $v->validated();
        $errors = $v->errors();

        if (!isset($errors['email'])) {
            $data['email'] = mb_strtolower($data['email']);
            if (User::emailExists($data['email'], $id)) {
                $errors['email'] = 'Ya existe un usuario con ese email.';
            }
        }
        if (!isset($errors['role_id'])) {
            $data['role'] = Role::find($data['role_id']);
            if ($data['role'] === null) {
                $errors['role_id'] = 'Elegí un rol válido.';
            }
        }

        $data['locales'] = array_values(array_unique(array_filter(
            array_map('intval', (array) ($input['locales'] ?? []))
        )));

        return [$data, $errors];
    }
}
