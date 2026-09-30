<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Categoria;
use App\Models\Producto;
use App\Requests\ProductoRequest;
use App\Services\ImageService;
use App\Services\ProductoImportService;
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

    /** Subir el Excel con todos los productos de la empresa. */
    public function importarForm(): void
    {
        $this->view('admin/productos/importar', [
            'title'     => 'Subir Excel de productos',
            'resultado' => $_SESSION['import_resultado'] ?? null,
        ]);
        unset($_SESSION['import_resultado']);
    }

    public function importar(): void
    {
        try {
            $resultado = ProductoImportService::importar(ProductoImportService::leer($_FILES['archivo'] ?? []));
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/admin/productos/importar');
        }

        // Solo los primeros errores: con eso alcanza para corregir el Excel.
        $resultado['total_errores'] = count($resultado['errores']);
        $resultado['errores'] = array_slice($resultado['errores'], 0, 50, true);
        $_SESSION['import_resultado'] = $resultado;
        redirect('/admin/productos/importar');
    }

    public function plantilla(): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla-productos.csv"');
        header('Cache-Control: no-store');
        echo ProductoImportService::plantilla();
        exit;
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
            !empty($_POST['quitar_imagen'])
        );
    }

    private function form(string $title, ?array $producto): void
    {
        $this->view('admin/productos/form', [
            'title'      => $title,
            'producto'   => $producto,
            'categorias' => Categoria::opciones($producto['categoria_id'] ?? null),
            'scripts'    => ['assets/js/escaner.js'],
        ]);
    }
}
