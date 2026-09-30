<?php
/** @var App\Core\Router $router */

use App\Controllers\Admin\CategoriaController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LocalController;
use App\Controllers\Admin\ProductoController as AdminProductoController;
use App\Controllers\Admin\PromocionController as AdminPromocionController;
use App\Controllers\Admin\RelevamientoController;
use App\Controllers\Admin\TareaController as AdminTareaController;
use App\Controllers\TareaController;
use App\Controllers\VisitaPromocionController;
use App\Controllers\Admin\UsuarioController;
use App\Controllers\AuthController;
use App\Controllers\FotoController;
use App\Controllers\ObservacionController;
use App\Controllers\VisitaProductoController;
use App\Controllers\InicioController;
use App\Controllers\PerfilController;
use App\Controllers\ProductoController;
use App\Controllers\Admin\FotoController as AdminFotoController;
use App\Controllers\Admin\ConfiguracionController;
use App\Controllers\HistorialController;
use App\Controllers\PwaController;
use App\Controllers\ReporteController;
use App\Controllers\VisitaController;

// App instalable (PWA): sin sesión, el navegador los pide solo
$router->get('/manifest.webmanifest', [PwaController::class, 'manifest']);
$router->get('/sw.js', [PwaController::class, 'serviceWorker']);
$router->get('/offline', [PwaController::class, 'offline']);

// Acceso
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

// Repositor
$router->get('/', [InicioController::class, 'index'], ['auth']);
$router->post('/locales/detectar', [InicioController::class, 'detectar'], ['auth']);

$router->post('/visitas', [VisitaController::class, 'iniciar'], ['auth']);
$router->get('/visitas/{id}', [VisitaController::class, 'show'], ['auth']);
$router->post('/visitas/{id}/finalizar', [VisitaController::class, 'finalizar'], ['auth']);
$router->post('/visitas/{id}/eliminar', [VisitaController::class, 'eliminar'], ['auth']);
$router->post('/visitas/{id}/productos/{pid}/quitar', [VisitaProductoController::class, 'quitar'], ['auth']);
$router->post('/visitas/{id}/promociones/{pid}/eliminar', [VisitaPromocionController::class, 'eliminar'], ['auth']);

$router->get('/visitas/{id}/productos', [VisitaProductoController::class, 'lista'], ['auth']);
$router->get('/visitas/{id}/buscar', [VisitaProductoController::class, 'buscar'], ['auth']);
$router->get('/visitas/{id}/productos/{pid}', [VisitaProductoController::class, 'show'], ['auth']);
$router->post('/visitas/{id}/productos/{pid}/stock', [VisitaProductoController::class, 'guardarStock'], ['auth']);
$router->post('/visitas/{id}/productos/{pid}/vencimientos', [VisitaProductoController::class, 'agregarVencimiento'], ['auth']);
$router->post('/visitas/{id}/productos/{pid}/vencimientos/copiar', [VisitaProductoController::class, 'copiarVencimientos'], ['auth']);
$router->post('/visitas/{id}/vencimientos/{vid}/eliminar', [VisitaProductoController::class, 'eliminarVencimiento'], ['auth']);

$router->post('/visitas/{id}/fotos', [FotoController::class, 'subir'], ['auth']);
$router->get('/fotos/{id}', [FotoController::class, 'ver'], ['auth']);
$router->post('/fotos/{id}/eliminar', [FotoController::class, 'eliminar'], ['auth']);

$router->get('/visitas/{id}/promociones/crear', [VisitaPromocionController::class, 'crear'], ['auth']);
$router->post('/visitas/{id}/promociones', [VisitaPromocionController::class, 'guardar'], ['auth']);
$router->get('/visitas/{id}/conteo', [VisitaPromocionController::class, 'conteo'], ['auth']);
$router->post('/visitas/{id}/conteo/{pid}', [VisitaPromocionController::class, 'guardarConteo'], ['auth']);
$router->post('/visitas/{id}/tareas/{tid}', [VisitaPromocionController::class, 'tarea'], ['auth']);

$router->post('/visitas/{id}/observaciones', [ObservacionController::class, 'crear'], ['auth']);
$router->post('/observaciones/{id}/eliminar', [ObservacionController::class, 'eliminar'], ['auth']);

$router->get('/perfil', [PerfilController::class, 'show'], ['auth']);
$router->get('/productos', [ProductoController::class, 'index'], ['auth']);
$router->get('/productos/buscar', [ProductoController::class, 'buscar'], ['auth']);
$router->get('/productos/codigo', [ProductoController::class, 'porCodigo'], ['auth']);
$router->get('/productos/crear', [ProductoController::class, 'create'], ['auth']);
$router->post('/productos', [ProductoController::class, 'store'], ['auth']);
$router->get('/productos/{id}', [ProductoController::class, 'show'], ['auth']);
$router->get('/productos/{id}/imagen', [ProductoController::class, 'imagen'], ['auth']);
$router->get('/tareas', [TareaController::class, 'index'], ['auth']);
$router->get('/historial', [HistorialController::class, 'index'], ['auth']);
$router->get('/mensaje', [HistorialController::class, 'mensajeDelDia'], ['auth']);
$router->get('/visitas/{id}/mensaje', [HistorialController::class, 'mensajeDeVisita'], ['auth']);
$router->get('/reportes', [ReporteController::class, 'index'], ['auth']);
$router->get('/reportes/csv', [ReporteController::class, 'csv'], ['auth']);

// Administración
$admin = ['auth', 'admin'];
$router->get('/admin', [DashboardController::class, 'index'], $admin);

$router->get('/admin/relevamientos', [RelevamientoController::class, 'index'], $admin);
$router->get('/admin/fotos', [AdminFotoController::class, 'index'], $admin);
$router->get('/admin/configuracion', [ConfiguracionController::class, 'index'], $admin);
$router->post('/admin/configuracion', [ConfiguracionController::class, 'guardar'], $admin);

$router->get('/admin/locales', [LocalController::class, 'index'], $admin);
$router->post('/admin/locales/ubicacion', [LocalController::class, 'ubicacion'], $admin);
$router->get('/admin/locales/crear', [LocalController::class, 'create'], $admin);
$router->post('/admin/locales', [LocalController::class, 'store'], $admin);
$router->get('/admin/locales/{id}/editar', [LocalController::class, 'edit'], $admin);
$router->post('/admin/locales/{id}', [LocalController::class, 'update'], $admin);
$router->post('/admin/locales/{id}/estado', [LocalController::class, 'toggle'], $admin);
$router->post('/admin/locales/{id}/eliminar', [LocalController::class, 'eliminar'], $admin);

$router->get('/admin/productos', [AdminProductoController::class, 'index'], $admin);
$router->get('/admin/productos/importar', [AdminProductoController::class, 'importarForm'], $admin);
$router->post('/admin/productos/importar', [AdminProductoController::class, 'importar'], $admin);
$router->get('/admin/productos/plantilla', [AdminProductoController::class, 'plantilla'], $admin);
$router->get('/admin/productos/crear', [AdminProductoController::class, 'create'], $admin);
$router->post('/admin/productos', [AdminProductoController::class, 'store'], $admin);
$router->get('/admin/productos/{id}/editar', [AdminProductoController::class, 'edit'], $admin);
$router->post('/admin/productos/{id}', [AdminProductoController::class, 'update'], $admin);
$router->post('/admin/productos/{id}/estado', [AdminProductoController::class, 'toggle'], $admin);
$router->post('/admin/productos/{id}/eliminar', [AdminProductoController::class, 'eliminar'], $admin);

$router->get('/admin/promociones', [AdminPromocionController::class, 'index'], $admin);
$router->get('/admin/promociones/crear', [AdminPromocionController::class, 'create'], $admin);
$router->post('/admin/promociones', [AdminPromocionController::class, 'store'], $admin);
$router->get('/admin/promociones/{id}/editar', [AdminPromocionController::class, 'edit'], $admin);
$router->post('/admin/promociones/{id}', [AdminPromocionController::class, 'update'], $admin);
$router->post('/admin/promociones/{id}/cancelar', [AdminPromocionController::class, 'cancelar'], $admin);
$router->post('/admin/promociones/{id}/eliminar', [AdminPromocionController::class, 'eliminar'], $admin);

$router->get('/admin/tareas', [AdminTareaController::class, 'index'], $admin);
$router->get('/admin/tareas/crear', [AdminTareaController::class, 'create'], $admin);
$router->post('/admin/tareas', [AdminTareaController::class, 'store'], $admin);
$router->get('/admin/tareas/{id}/editar', [AdminTareaController::class, 'edit'], $admin);
$router->post('/admin/tareas/{id}', [AdminTareaController::class, 'update'], $admin);
$router->post('/admin/tareas/{id}/eliminar', [AdminTareaController::class, 'eliminar'], $admin);

$router->get('/admin/categorias', [CategoriaController::class, 'index'], $admin);
$router->post('/admin/categorias', [CategoriaController::class, 'store'], $admin);
$router->get('/admin/categorias/{id}/editar', [CategoriaController::class, 'edit'], $admin);
$router->post('/admin/categorias/{id}', [CategoriaController::class, 'update'], $admin);
$router->post('/admin/categorias/{id}/eliminar', [CategoriaController::class, 'eliminar'], $admin);

$router->get('/admin/usuarios', [UsuarioController::class, 'index'], $admin);
$router->get('/admin/usuarios/crear', [UsuarioController::class, 'create'], $admin);
$router->post('/admin/usuarios', [UsuarioController::class, 'store'], $admin);
$router->get('/admin/usuarios/{id}/editar', [UsuarioController::class, 'edit'], $admin);
$router->post('/admin/usuarios/{id}', [UsuarioController::class, 'update'], $admin);
$router->post('/admin/usuarios/{id}/estado', [UsuarioController::class, 'toggle'], $admin);
$router->post('/admin/usuarios/{id}/eliminar', [UsuarioController::class, 'eliminar'], $admin);
