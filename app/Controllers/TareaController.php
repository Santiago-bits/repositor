<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Local;
use App\Models\Relevamiento;
use App\Services\ConteoService;
use App\Services\TareaService;

/** Tareas de hoy en todos los locales del usuario. */
final class TareaController extends Controller
{
    public function index(): void
    {
        $user = auth();
        $locales = [];
        foreach (Local::paraUsuario($user) as $local) {
            $tareas = TareaService::delDia((int) $local['id']);
            $locales[] = $local + [
                'tareas'     => $tareas,
                'pendientes' => count(array_filter($tareas, fn ($t) => !$t['hecha'])),
                'conteo'     => ConteoService::pendientes((int) $local['id']),
            ];
        }
        // Primero los locales con más cosas pendientes.
        usort($locales, fn ($a, $b) => [$b['pendientes'] + $b['conteo'], $a['nombre']] <=> [$a['pendientes'] + $a['conteo'], $b['nombre']]);

        $this->view('app/tareas', [
            'title'   => 'Tareas',
            'locales' => $locales,
            'abierta' => Relevamiento::abiertaDeUsuario((int) $user['id']),
        ]);
    }
}
