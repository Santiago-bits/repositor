<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Local;
use App\Models\Role;
use App\Models\User;
use App\Requests\UsuarioRequest;
use App\Services\UsuarioService;

final class UsuarioController extends Controller
{
    public function index(): void
    {
        $this->view('admin/usuarios/index', ['title' => 'Usuarios', 'usuarios' => User::all()]);
    }

    public function create(): void
    {
        $this->form('Nuevo usuario', null, []);
    }

    public function store(): void
    {
        [$data, $errors] = UsuarioRequest::validate($_POST);
        if ($errors) {
            $this->backWithErrors($errors, $this->oldInput(), '/admin/usuarios/crear');
        }

        UsuarioService::guardar($data);
        flash('success', "Usuario {$data['nombre']} {$data['apellido']} creado.");
        redirect('/admin/usuarios');
    }

    public function edit(int $id): void
    {
        $usuario = $this->notFoundUnless(User::find($id));
        $this->form('Editar usuario', $usuario, User::localIds($id));
    }

    public function update(int $id): void
    {
        $this->notFoundUnless(User::find($id));

        [$data, $errors] = UsuarioRequest::validate($_POST, $id);

        // Evita que el admin se quede afuera de su propio panel.
        if ($id === Auth::id()) {
            if (!$data['activo']) {
                $errors['activo'] = 'No podés desactivar tu propio usuario.';
            }
            if (isset($data['role']) && $data['role']['slug'] !== 'admin') {
                $errors['role_id'] = 'No podés quitarte el rol de administrador.';
            }
        }
        if ($errors) {
            $this->backWithErrors($errors, $this->oldInput(), "/admin/usuarios/{$id}/editar");
        }

        UsuarioService::guardar($data, $id);
        flash('success', "Usuario {$data['nombre']} {$data['apellido']} actualizado.");
        redirect('/admin/usuarios');
    }

    public function toggle(int $id): void
    {
        $usuario = $this->notFoundUnless(User::find($id));
        if ($id === Auth::id()) {
            flash('error', 'No podés desactivar tu propio usuario.');
            redirect('/admin/usuarios');
        }

        $activo = UsuarioService::cambiarEstado($usuario);
        flash('success', "Usuario {$usuario['nombre']} {$usuario['apellido']} " . ($activo ? 'activado.' : 'desactivado.'));
        redirect('/admin/usuarios');
    }

    private function form(string $title, ?array $usuario, array $asignados): void
    {
        $this->view('admin/usuarios/form', [
            'title'     => $title,
            'usuario'   => $usuario,
            'roles'     => Role::all(),
            'locales'   => Local::all(),
            'asignados' => $asignados,
        ]);
    }

    /** Lo escrito en el formulario, sin la contraseña. */
    private function oldInput(): array
    {
        $old = $_POST;
        unset($old['password'], $old['_token']);
        $old['locales'] ??= [];
        return $old;
    }
}
