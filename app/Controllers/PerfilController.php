<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\User;
use App\Services\ImageService;
use RuntimeException;

/** Mi cuenta: foto, datos, contraseña y sesiones. Cada uno solo toca lo suyo. */
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

    public function datos(): void
    {
        $nombre = trim((string) Request::input('nombre', ''));
        $apellido = trim((string) Request::input('apellido', ''));

        $errores = [];
        if ($nombre === '' || mb_strlen($nombre) > 80) {
            $errores['nombre'] = 'Escribí tu nombre (hasta 80 letras).';
        }
        if ($apellido === '' || mb_strlen($apellido) > 80) {
            $errores['apellido'] = 'Escribí tu apellido (hasta 80 letras).';
        }
        if ($errores) {
            $this->backWithErrors($errores, $_POST, '/perfil');
        }

        User::actualizarDatos((int) Auth::id(), $nombre, $apellido);
        flash('success', 'Datos guardados.');
        redirect('/perfil');
    }

    /** Cambiar el email con el que se entra (con contraseña o con Google). Pide la contraseña actual. */
    public function email(): void
    {
        $id = (int) Auth::id();
        $email = mb_strtolower(trim((string) Request::input('email', '')));
        $password = (string) Request::input('email_password', '');

        $errores = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errores['email'] = 'Escribí un email válido (ej: tunombre@gmail.com).';
        } elseif ($email === mb_strtolower((string) auth()['email'])) {
            $errores['email'] = 'Es el mismo que ya tenés.';
        } elseif (User::emailExists($email, $id) || User::googleEmailEnUso($email, $id)) {
            $errores['email'] = 'Ese email ya lo usa otro usuario.';
        }
        if (!$errores && !password_verify($password, User::passwordHash($id))) {
            $errores['email_password'] = 'La contraseña no es correcta.';
        }
        if ($errores) {
            $this->backWithErrors($errores, ['email' => $email], '/perfil#gmail');
        }

        User::cambiarEmail($id, $email);
        flash('success', "Listo: ahora entrás con {$email}.");
        redirect('/perfil');
    }

    public function foto(): void
    {
        if (ImageService::sinArchivo($_FILES['foto'] ?? null)) {
            $this->backWithErrors(['foto' => 'Elegí una foto.'], [], '/perfil');
        }
        try {
            $path = ImageService::guardar($_FILES['foto'], 'usuarios', 400);
        } catch (RuntimeException $e) {
            $this->backWithErrors(['foto' => $e->getMessage()], [], '/perfil');
        }

        $anterior = auth()['foto_path'] ?? null;
        User::setFoto((int) Auth::id(), $path);
        ImageService::eliminar($anterior);
        flash('success', 'Foto actualizada.');
        redirect('/perfil');
    }

    public function quitarFoto(): void
    {
        $anterior = auth()['foto_path'] ?? null;
        User::setFoto((int) Auth::id(), null);
        ImageService::eliminar($anterior);
        flash('success', 'Foto quitada.');
        redirect('/perfil');
    }

    /** Cambiar la contraseña: pide la actual y cierra la sesión en los demás dispositivos. */
    public function password(): void
    {
        $id = (int) Auth::id();
        $actual = (string) Request::input('actual', '');
        $nueva = (string) Request::input('nueva', '');
        $repetir = (string) Request::input('repetir', '');

        $errores = [];
        if (!password_verify($actual, User::passwordHash($id))) {
            $errores['actual'] = 'La contraseña actual no es correcta.';
        } elseif (mb_strlen($nueva) < 8 || mb_strlen($nueva) > 72) {
            $errores['nueva'] = 'La contraseña nueva tiene que tener entre 8 y 72 caracteres.';
        } elseif ($nueva !== $repetir) {
            $errores['repetir'] = 'Las dos contraseñas nuevas no coinciden.';
        } elseif ($nueva === $actual) {
            $errores['nueva'] = 'La contraseña nueva es igual a la actual.';
        }
        if ($errores) {
            $this->backWithErrors($errores, [], '/perfil#password');
        }

        User::updatePassword($id, $nueva);
        Auth::cerrarOtrasSesiones($id);
        flash('success', 'Contraseña cambiada. Si tenías la app abierta en otro celular, ahí vas a tener que volver a entrar.');
        redirect('/perfil');
    }

    public function cerrarSesiones(): void
    {
        Auth::cerrarOtrasSesiones((int) Auth::id());
        flash('success', 'Listo: se cerró tu sesión en los demás dispositivos. En este seguís adentro.');
        redirect('/perfil');
    }

    /** Foto de perfil (la ven todos los usuarios: aparece en Equipo y en Usuarios). */
    public function verFoto(int $id): never
    {
        $user = User::find($id);
        if ($user === null || empty($user['foto_path'])) {
            View::error(404);
        }
        ImageService::enviar($user['foto_path']);
    }
}
