<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Local;
use App\Models\User;
use App\Models\UserToken;

final class UsuarioService
{
    /** Crea o actualiza un usuario junto con sus locales asignados. */
    public static function guardar(array $data, ?int $id = null): int
    {
        return Database::transaction(function () use ($data, $id): int {
            if ($id === null) {
                $id = User::create($data);
            } else {
                User::update($id, $data);
            }

            User::syncLocales($id, Local::existingIds($data['locales']));

            if (!$data['activo'] || !empty($data['password'])) {
                UserToken::deleteForUser($id); // cierra sesiones recordadas en otros dispositivos
            }
            return $id;
        });
    }

    public static function cambiarEstado(array $usuario): bool
    {
        $activo = !$usuario['activo'];
        User::setActivo((int) $usuario['id'], $activo);
        if (!$activo) {
            UserToken::deleteForUser((int) $usuario['id']);
        }
        return $activo;
    }
}
