<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Local;

/**
 * Compara la ubicación del celular con los locales del usuario.
 * La ubicación recibida solo se usa para este cálculo: no se guarda.
 */
final class DeteccionLocalService
{
    /** El margen de error del GPS se descuenta hasta este tope, para que una señal mala no "detecte" cualquier local. */
    private const MARGEN_MAXIMO = 100;
    private const PRECISION_BAJA = 150;

    public static function detectar(array $user, float $lat, float $lng, float $precision): array
    {
        $margen = min(max($precision, 0), self::MARGEN_MAXIMO);
        $candidatos = [];
        $sinCoordenadas = 0;

        foreach (Local::paraUsuario($user) as $local) {
            if ($local['latitud'] === null || $local['longitud'] === null) {
                $sinCoordenadas++;
                continue;
            }
            $distancia = self::distancia($lat, $lng, (float) $local['latitud'], (float) $local['longitud']);
            $candidatos[] = [
                'id'        => (int) $local['id'],
                'nombre'    => $local['nombre'],
                'direccion' => $local['direccion'],
                'icono'     => Local::ICONOS[$local['tipo']],
                'distancia' => (int) round($distancia),
                'texto'     => self::textoDistancia($distancia),
                'dentro'    => $distancia - $margen <= (int) $local['radio_m'],
            ];
        }

        usort($candidatos, fn ($a, $b) => $a['distancia'] <=> $b['distancia']);
        $dentro = array_values(array_filter($candidatos, fn ($c) => $c['dentro']));

        $estado = match (count($dentro)) {
            0       => 'ninguno',
            1       => 'detectado',
            default => 'varios',
        };

        $tareas = [];
        if ($estado === 'detectado') {
            foreach (TareaService::delDia($dentro[0]['id']) as $t) {
                $tareas[] = ['nombre' => $t['nombre'], 'prioridad' => $t['prioridad'], 'hecha' => $t['hecha']];
            }
        }

        return [
            'estado'          => $estado,
            'locales'         => array_slice($dentro ?: $candidatos, 0, $estado === 'ninguno' ? 3 : 5),
            'tareas'          => $tareas,
            'precision'       => (int) round($precision),
            'precision_baja'  => $precision > self::PRECISION_BAJA,
            'sin_coordenadas' => $sinCoordenadas,
            // Locales de los que seguro estás afuera: si la visita abierta es de uno de estos, se cierra sola.
            'fuera'           => $precision > self::PRECISION_BAJA ? [] : array_values(array_map(
                fn ($c) => $c['id'],
                array_filter($candidatos, fn ($c) => !$c['dentro'])
            )),
        ];
    }

    /** Distancia en metros entre dos coordenadas (fórmula de Haversine). */
    public static function distancia(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }

    private static function textoDistancia(float $metros): string
    {
        return $metros < 1000
            ? ((int) round($metros)) . ' m'
            : number_format($metros / 1000, 1, ',', '.') . ' km';
    }
}
