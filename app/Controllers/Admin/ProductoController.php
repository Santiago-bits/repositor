<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Categoria;
use App\Models\Local;
use App\Models\Producto;
use App\Models\ProductoLocal;
use App\Requests\ProductoRequest;
use App\Services\ImageService;
use App\Services\ProductoService;
use RuntimeException;

final class ProductoController extends Controller
{
    public function index(): void
    {
        $buscar = trim((string) Request::input('q', ''));

        $this->view('admin/productos/index', [
            'title'     => 'Productos',
            'buscar'    => $buscar,
            'productos' => $buscar !== '' ? Producto::buscar($buscar, false, 200) : Producto::todos(),
        ]);
    }

    public function create(): void
    {
        $this->form('Nuevo producto', null);
    }

    public function store(): void
    {
        $id = $this->guardar(null, '/admin/productos/crear');
        flash('success', 'Producto creado.');
        redirect('/admin/productos/' . $id . '/editar');
    }

    public function edit(int $id): void
    {
        $this->form('Editar producto', $this->notFoundUnless(Producto::find($id)));
    }

    public function update(int $id): void
    {
        $this->notFoundUnless(Producto::find($id));
        $this->guardar($id, "/admin/productos/{$id}/editar");
        flash('success', 'Producto actualizado.');
        redirect('/admin/productos');
    }

    public function toggle(int $id): void
    {
        $producto = $this->notFoundUnless(Producto::find($id));
        $activo = !$producto['activo'];
        Producto::setActivo($id, $activo);

        flash('success', '«' . Producto::nombreCompleto($producto) . '» ' . ($activo ? 'activado.' : 'desactivado.'));
        redirect('/admin/productos');
    }

    public function eliminar(int $id): void
    {
        $producto = $this->notFoundUnless(Producto::find($id));
        Database::transaction(fn () => Producto::eliminar($id));
        flash('success', '«' . Producto::nombreCompleto($producto) . '» borrado.');
        redirect('/admin/productos');
    }

    private function guardar(?int $id, string $volver): int
    {
        [$data, $errors] = ProductoRequest::validate($_POST, $id);

        $imagen = null;
        if (!$errors && !ImageService::sinArchivo($_FILES['imagen'] ?? null)) {
            try {
                $imagen = ImageService::guardar($_FILES['imagen'], 'productos', 800);
            } catch (RuntimeException $e) {
                $errors['imagen'] = $e->getMessage();
            }
        }
        if ($errors) {
            $this->backWithErrors($errors, $_POST, $volver);
        }

        return ProductoService::guardar(
            $data,
            $id,
            $imagen,
            !empty($_POST['quitar_imagen']),
            ProductoRequest::locales($_POST)
        );
    }

    private function form(string $title, ?array $producto): void
    {
        $this->view('admin/productos/form', [
            'title'      => $title,
            'producto'   => $producto,
            'categorias' => Categoria::opciones($producto['categoria_id'] ?? null),
            'locales'    => Local::all(),
            // Producto nuevo: marcado en todos los locales activos (es lo más común).
            'asignados'  => $producto ? ProductoLocal::dePorProducto((int) $producto['id']) : null,
            'scripts'    => ['assets/js/escaner.js'],
        ]);
    }
}
