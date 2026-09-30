<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Configuracion;

final class ConfiguracionController extends Controller
{
    /** clave => [etiqueta, mínimo, máximo, ayuda] */
    private const CAMPOS = [
        'vencimiento_dias_critico'  => ['Vence en pocos días', 1, 60, 'Hasta cuántos días antes se marca en naranja.'],
        'vencimiento_dias_proximo'  => ['Vence próximamente', 1, 365, 'Hasta cuántos días antes se marca en amarillo. Más allá: "sin riesgo".'],
        'conteo_promos_dias_gracia' => ['Días para contar una promo', 0, 14, 'Cuántos días después de terminada una promo sigue apareciendo en el conteo.'],
    ];

    public function index(): void
    {
        $valores = [];
        foreach (array_keys(self::CAMPOS) as $clave) {
            $valores[$clave] = Configuracion::get($clave, '');
        }
        $this->view('admin/configuracion', ['title' => 'Configuración', 'campos' => self::CAMPOS, 'valores' => $valores]);
    }

    public function guardar(): void
    {
        $datos = [];
        $errores = [];
        foreach (self::CAMPOS as $clave => [$etiqueta, $min, $max]) {
            $valor = filter_var($_POST[$clave] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
            if ($valor === false) {
                $errores[$clave] = "{$etiqueta}: tiene que ser un número entre {$min} y {$max}.";
            } else {
                $datos[$clave] = $valor;
            }
        }
        if (!$errores && $datos['vencimiento_dias_proximo'] < $datos['vencimiento_dias_critico']) {
            $errores['vencimiento_dias_proximo'] = '"Vence próximamente" tiene que ser mayor o igual que "vence en pocos días".';
        }
        if ($errores) {
            $this->backWithErrors($errores, $_POST, '/admin/configuracion');
        }

        foreach ($datos as $clave => $valor) {
            Configuracion::set($clave, (string) $valor);
        }
        flash('success', 'Configuración guardada.');
        redirect('/admin/configuracion');
    }
}
