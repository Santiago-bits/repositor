<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Relevamiento;
use App\Services\EquipoService;

/** Supervisar al equipo: cuándo entró cada uno y qué hizo (visitas, faltantes, vencimientos, fotos). */
final class EquipoController extends Controller
{
    private const PERIODOS = [1 => 'Hoy', 7 => '7 días', 30 => '30 días'];

    public function index(): void
    {
        $dias = $this->dias();
        $this->view('admin/equipo/index', [
            'title'    => 'Equipo',
            'dias'     => $dias,
            'periodos' => self::PERIODOS,
            'miembros' => EquipoService::miembros($dias),
        ]);
    }

    public function show(int $id): void
    {
        $dias = $this->dias();
        $miembro = $this->notFoundUnless(EquipoService::miembro($id, $dias));

        $porFecha = [];
        foreach (Relevamiento::historial($id, date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'))) as $v) {
            $porFecha[$v['fecha']][] = $v;
        }

        $this->view('admin/equipo/show', [
            'title'    => $miembro['nombre'] . ' ' . $miembro['apellido'],
            'dias'     => $dias,
            'periodos' => self::PERIODOS,
            'm'        => $miembro,
            'porFecha' => $porFecha,
        ]);
    }

    private function dias(): int
    {
        $dias = (int) Request::input('dias', 7);
        return isset(self::PERIODOS[$dias]) ? $dias : 7;
    }
}
