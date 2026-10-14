<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\LoginIntento;
use App\Models\User;
use App\Services\GoogleAuthService;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Ingresar'], 'auth');
    }

    public function login(): void
    {
        $email = mb_strtolower(trim((string) Request::input('email', '')));
        $password = (string) Request::input('password', '');
        $remember = Request::input('remember') === '1';
        $ip = Request::ip();
        $old = ['email' => $email, 'remember' => $remember ? '1' : ''];

        if (LoginIntento::recientes($ip, LOGIN_BLOQUEO_MINUTOS) >= LOGIN_MAX_INTENTOS) {
            $this->backWithErrors(
                ['email' => 'Demasiados intentos fallidos. Esperá ' . LOGIN_BLOQUEO_MINUTOS . ' minutos y probá de nuevo.'],
                $old,
                '/login'
            );
        }

        $user = $email !== '' ? User::findForLogin($email) : null;
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            LoginIntento::registrar($ip, $email);
            $this->backWithErrors(['email' => 'Email o contraseña incorrectos.'], $old, '/login');
        }
        if (!$user['activo']) {
            $this->backWithErrors(['email' => 'Tu usuario está desactivado. Consultá con el administrador.'], $old, '/login');
        }

        LoginIntento::limpiar($ip);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        Auth::login((int) $user['id'], $remember);
        redirect(Session::pull('intended', '/'));
    }

    /** Botón "Iniciar sesión con Google": solo entra quien ya tiene usuario (JSON). */
    public function loginGoogle(): never
    {
        $ip = Request::ip();
        if (LoginIntento::recientes($ip, LOGIN_BLOQUEO_MINUTOS) >= LOGIN_MAX_INTENTOS) {
            $this->json(['ok' => false, 'message' => 'Demasiados intentos. Esperá ' . LOGIN_BLOQUEO_MINUTOS . ' minutos.'], 429);
        }

        $google = GoogleAuthService::verifyIdToken((string) Request::input('credential', ''));
        if ($google === null) {
            LoginIntento::registrar($ip, '');
            $this->json(['ok' => false, 'message' => 'No se pudo verificar la cuenta de Google. Probá de nuevo.'], 401);
        }

        $email = mb_strtolower($google['email']);
        $user = User::findForGoogle($email);
        if ($user === null) {
            LoginIntento::registrar($ip, $email);
            $this->json(['ok' => false, 'message' => 'Esa cuenta de Google no está vinculada a ningún usuario. Entrá con tu email y vinculala en Perfil.'], 403);
        }
        if (!$user['activo']) {
            $this->json(['ok' => false, 'message' => 'Tu usuario está desactivado. Consultá con el administrador.'], 403);
        }

        LoginIntento::limpiar($ip);
        Auth::login((int) $user['id'], true);
        $this->json(['ok' => true, 'redirect' => url(Session::pull('intended', '/'))]);
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
