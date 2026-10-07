<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Relevamiento;
use App\Requests\ProductoRequest;
use App\Services\ImageService;
use App\Services\ProductoService;

/** Productos desde el celular: buscar, escanear, ver ficha y alta rápida. */
final class ProductoController extends Controller
{
    private const SCRIPTS = ['assets/js/escaner.js', 'assets/js/productos.js'];

    public function index(): void
    {
        $q = trim((string) Request::input('q', ''));

        $this->view('app/productos/index', [
            'title'      => 'Productos',
            'q'          => $q,
            'resultados' => $q !== '' ? Producto::buscar($q, !is_admin()) : [],
            'scripts'    => self::SCRIPTS,
        ]);
    }

    /** Fragmento HTML con resultados (búsqueda mientras se escribe). */
    public function buscar(): void
    {
        $q = trim((string) Request::input('q', ''));
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        View::render('partials/productos-resultados', [
            'q'          => $q,
            'resultados' => $q !== '' ? Producto::buscar($q, !is_admin()) : [],
        ], null);
    }

    /** Productos sin código de barras para asociarles el que se acaba de escanear (JSON). */
    public function sinCodigo(): never
    {
        $q = trim((string) Request::input('q', ''));
        $lista = $q === '' ? [] : Producto::buscarSinCodigo($q);
        $this->json(['productos' => array_map(fn ($p) => [
            'id'     => (int) $p['id'],
            'nombre' => Producto::nombreCompleto($p),
            'marca'  => $p['marca'],
        ], $lista)]);
    }

    /** "Esta lata es Andes Oro": guarda el código escaneado en un producto que no tenía (JSON). */
    public function asociarCodigo(int $id): never
    {
        $codigo = preg_replace('/\s+/', '', (string) Request::input('codigo', ''));
        if (!preg_match(ProductoRequest::CODIGO_REGEX, $codigo)) {
            $this->json(['ok' => false, 'message' => 'El código leído no es válido.'], 422);
        }
        $producto = Producto::find($id);
        if ($producto === null) {
            $this->json(['ok' => false, 'message' => 'Ese producto no existe.'], 404);
        }
        if (Producto::codigoExiste($codigo, $id)) {
            $this->json(['ok' => false, 'message' => 'Ese código ya está asociado a otro producto.'], 409);
        }
        if (!Producto::asociarCodigo($id, $codigo)) {
            $this->json(['ok' => false, 'message' => 'Ese producto ya tiene otro código de barras.'], 409);
        }

        $this->json([
            'ok'      => true,
            'id'      => $id,
            'url'     => url('/productos/' . $id),
            'message' => 'Listo: la próxima vez que escanees este código vas a ver ' . Producto::nombreCompleto($producto) . '.',
        ]);
    }

    /** Resultado de un código escaneado (JSON). */
    public function porCodigo(): never
    {
        $codigo = preg_replace('/\s+/', '', (string) Request::input('c', ''));
        if (!preg_match(ProductoRequest::CODIGO_REGEX, $codigo)) {
            $this->json(['found' => false, 'message' => 'El código leído no es válido. Probá de nuevo.'], 422);
        }

        $producto = Producto::findByCodigo($codigo);
        if ($producto !== null) {
            $this->json([
                'found'  => true,
                'id'     => (int) $producto['id'],
                'url'    => url('/productos/' . $producto['id']),
                'nombre' => Producto::nombreCompleto($producto),
            ]);
        }
        $this->json(['found' => false, 'codigo' => $codigo, 'crear_url' => url('/productos/crear?codigo=' . rawurlencode($codigo))]);
    }

    public function show(int $id): void
    {
        $producto = $this->notFoundUnless(Producto::find($id));
        if (!$producto['activo'] && !is_admin()) {
            View::error(404);
        }
        $user = auth();

        $this->view('app/productos/show', [
            'title'     => Producto::nombreCompleto($producto),
            'producto'  => $producto,
            'historial' => Producto::historial($id, $user),
        ]);
    }

    /** Alta rápida (por ejemplo, después de escanear un código que no existe). */
    public function create(): void
    {
        $codigo = preg_replace('/\s+/', '', (string) Request::input('codigo', ''));
        $nombre = trim((string) Request::input('nombre', ''));

        $this->view('app/productos/crear', [
            'title'      => 'Nuevo producto',
            'producto'   => ['codigo_barras' => $codigo, 'nombre' => $nombre],
            'escaneado'  => $codigo,
            'visitaId'   => $this->visitaAbiertaPropia((int) Request::input('visita', 0)),
            'categorias' => Categoria::opciones(),
            'scripts'    => ['assets/js/escaner.js'],
        ]);
    }

    public function store(): void
    {
        [$data, $errors] = ProductoRequest::validate($_POST);
        if ($errors) {
            $this->backWithErrors($errors, $_POST, '/productos/crear');
        }

        $id = ProductoService::guardar($data);
        flash('success', 'Producto «' . Producto::nombreCompleto($data) . '» registrado.');

        // Creado desde una visita: seguir directo a cargarle el stock.
        $visitaId = $this->visitaAbiertaPropia((int) Request::input('visita', 0));
        redirect($visitaId ? "/visitas/{$visitaId}/productos/{$id}" : '/productos/' . $id);
    }

    /** Devuelve el id si es una visita en proceso del usuario actual (o null). */
    private function visitaAbiertaPropia(int $visitaId): ?int
    {
        if ($visitaId <= 0) {
            return null;
        }
        $visita = Relevamiento::find($visitaId);
        return $visita && (int) $visita['user_id'] === Auth::id() && $visita['estado'] === 'en_proceso' ? $visitaId : null;
    }

    public function imagen(int $id): never
    {
        $producto = Producto::find($id);
        if ($producto === null || !$producto['imagen_path']) {
            View::error(404);
        }
        ImageService::enviar($producto['imagen_path']);
    }
}
