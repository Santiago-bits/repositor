<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class PerfilController extends Controller
{
    public function show(): void
    {
        $this->view('app/perfil', ['title' => 'Perfil', 'user' => auth()]);
    }
}
