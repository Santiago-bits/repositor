<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Categoria;
use App\Requests\CategoriaRequest;

final class CategoriaController extends Controller
{
    public function index(): void
    {
        $this->view('admin/categorias/index', [
            'title'       => 'Categorías',
            'arbol'       => Categoria::arbol(),
            'principales' => Categoria::principales(),
        ]);
    }

    public function store(): void
    {
        [$data, $errors] = CategoriaRequest::validate($_POST);
        if ($errors) {
            $this->backWithErrors($errors, $_POST, '/admin/categorias');
        }

        Categoria::create($data);
        flash('success', "Categoría «{$data['nombre']}» creada.");
        redirect('/admin/categorias');
    }

    public function edit(int $id): void
    {
        $categoria = $this->notFoundUnless(Categoria::find($id));

        $this->view('admin/categorias/form', [
            'title'       => 'Editar categoría',
            'categoria'   => $categoria,
            'principales' => array_filter(Categoria::principales(), fn ($c) => (int) $c['id'] !== $id),
            'tieneHijas'  => Categoria::tieneHijas($id),
        ]);
    }

    public function update(int $id): void
    {
        $this->notFoundUnless(Categoria::find($id));

        [$data, $errors] = CategoriaRequest::validate($_POST, $id);
        if ($errors) {
            $this->backWithErrors($errors, $_POST, "/admin/categorias/{$id}/editar");
        }

        Categoria::update($id, $data);
        flash('success', "Categoría «{$data['nombre']}» actualizada.");
        redirect('/admin/categorias');
    }
}
