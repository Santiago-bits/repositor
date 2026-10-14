<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class PerfilController extends Controller
{
    public function show(): void
    {
        $this->view('app/perfil', ['title' => 'Mi cuenta', 'user' => auth()]);
    }

    /** Gestión en el celular (en la PC está en el menú lateral). */
    public function gestion(): void
    {
        $this->view('app/gestion', ['title' => 'Gestión']);
    }
}
