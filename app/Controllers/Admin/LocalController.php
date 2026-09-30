<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Local;
use App\Requests\LocalRequest;

final class LocalController extends Controller
{
    public function index(): void
    {
        $buscar = trim((string) Request::input('q', ''));

        $this->view('admin/locales/index', [
            'title'   => 'Locales',
            'buscar'  => $buscar,
            'locales' => Local::all($buscar),
        ]);
    }

    public function create(): void
    {
        $this->view('admin/locales/form', ['title' => 'Nuevo local', 'local' => null]);
    }

    public function store(): void
    {
        [$data, $errors] = LocalRequest::validate($_POST);
        if ($errors) {
            $this->backWithErrors($errors, $_POST, '/admin/locales/crear');
        }

        Local::create($data);
        flash('success', "Local «{$data['nombre']}» creado.");
        redirect('/admin/locales');
    }

    public function edit(int $id): void
    {
        $local = $this->notFoundUnless(Local::find($id));
        $this->view('admin/locales/form', ['title' => 'Editar local', 'local' => $local]);
    }

    public function update(int $id): void
    {
        $this->notFoundUnless(Local::find($id));

        [$data, $errors] = LocalRequest::validate($_POST);
        if ($errors) {
            $this->backWithErrors($errors, $_POST, "/admin/locales/{$id}/editar");
        }

        Local::update($id, $data);
        flash('success', "Local «{$data['nombre']}» actualizado.");
        redirect('/admin/locales');
    }

    public function toggle(int $id): void
    {
        $local = $this->notFoundUnless(Local::find($id));
        $activo = !$local['activo'];
        Local::setActivo($id, $activo);

        flash('success', "Local «{$local['nombre']}» " . ($activo ? 'activado.' : 'desactivado.'));
        redirect('/admin/locales');
    }
}
