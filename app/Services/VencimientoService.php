<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Configuracion;

/** Clasifica una fecha de vencimiento según los rangos de Configuración. */
final class VencimientoService
{
    /** @return array{clave: string, etiqueta: string, clase: string} */
    public static function estado(string $fecha): array
    {
        $dias = (int) round((strtotime($fecha) - strtotime(date('Y-m-d'))) / 86400);
        $critico = (int) Configuracion::get('vencimiento_dias_critico', '3');
        $proximo = (int) Configuracion::get('vencimiento_dias_proximo', '15');

        [$clave, $etiqueta] = match (true) {
            $dias < 0         => ['vencido', 'Vencido'],
            $dias === 0       => ['hoy', 'Vence hoy'],
            $dias <= $critico => ['pocos', $dias === 1 ? 'Vence mañana' : "Vence en {$dias} días"],
            $dias <= $proximo => ['proximo', "En {$dias} días"],
            default           => ['ok', 'Sin riesgo'],
        };

        return ['clave' => $clave, 'etiqueta' => $etiqueta, 'clase' => 'venc-' . $clave];
    }
}
