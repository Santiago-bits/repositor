<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\User;
use App\Services\GoogleAuthService;

final class PerfilController extends Controller
{
    public function show(): void
    {
        $this->view('app/perfil', [
            'title'          => 'Perfil',
            'user'           => auth(),
            'googleClientId' => GoogleAuthService::clientId(),
        ]);
    }

    /** Guarda el Gmail con el que después se puede entrar con el botón de Google (JSON). */
    public function vincularGoogle(): never
    {
        $google = GoogleAuthService::verifyIdToken((string) Request::input('credential', ''));
        if ($google === null) {
            $this->json(['ok' => false, 'message' => 'No se pudo verificar la cuenta de Google. Probá de nuevo.'], 401);
        }

        $email = mb_strtolower($google['email']);
        $id = (int) Auth::id();
        if (User::googleEmailEnUso($email, $id)) {
            $this->json(['ok' => false, 'message' => 'Esa cuenta de Google ya está vinculada a otro usuario.'], 409);
        }

        User::setGoogleEmail($id, $email);
        flash('success', "Listo: ya podés entrar con Google ({$email}).");
        $this->json(['ok' => true, 'redirect' => url('/perfil')]);
    }

    public function desvincularGoogle(): never
    {
        User::setGoogleEmail((int) Auth::id(), null);
        flash('success', 'Cuenta de Google desvinculada.');
        redirect('/perfil');
    }
}
