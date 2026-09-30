<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\DashboardService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->view('admin/dashboard', [
            'title'     => 'Dashboard',
            'tarjetas'  => DashboardService::tarjetas(),
            'actividad' => DashboardService::actividadReciente(),
            'stats'     => DashboardService::estadisticas(),
        ]);
    }
}
