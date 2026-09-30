<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Local;
use App\Models\Promocion;

final class PromocionService
{
    /** Crea o actualiza una promoción con sus locales. */
    public static function guardar(array $data, array $localIds, int $userId, ?int $id = null): int
    {
        return Database::transaction(function () use ($data, $localIds, $userId, $id): int {
            if ($id === null) {
                $id = Promocion::create($data, $userId);
            } else {
                Promocion::update($id, $data);
            }
            Promocion::syncLocales($id, Local::existingIds($localIds));
            return $id;
        });
    }

    /** Fechas rápidas para el formulario: el fin de semana es lo más común. */
    public static function atajosFin(): array
    {
        $hoy = strtotime('today');
        $domingo = date('N', $hoy) === '7' ? $hoy : strtotime('next sunday', $hoy);
        $lunes = date('N', $hoy) === '1' ? $hoy : strtotime('next monday', $hoy);

        // Sin fechas repetidas (ej. un martes, "7 días" cae el mismo lunes).
        return array_unique([
            'Hasta el domingo ' . date('d/m', $domingo) => date('Y-m-d', $domingo),
            'Hasta el lunes ' . date('d/m', $lunes)     => date('Y-m-d', $lunes),
            '7 días'                                     => date('Y-m-d', strtotime('+6 days', $hoy)),
        ]);
    }

    /** Fin sugerido: el próximo lunes (las promos suelen ir de viernes a lunes). */
    public static function finPorDefecto(): string
    {
        $hoy = strtotime('today');
        return date('Y-m-d', date('N', $hoy) === '1' ? $hoy : strtotime('next monday', $hoy));
    }
}
