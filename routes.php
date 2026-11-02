<?php
/** @var App\Core\Router $router */

use App\Controllers\Admin\ConfiguracionController;
use App\Controllers\Admin\LocalController;
use App\Controllers\Admin\ProductoController as AdminProductoController;
use App\Controllers\Admin\UsuarioController;
use App\Controllers\AuthController;
use App\Controllers\AvisoController;
use App\Controllers\InicioController;
use App\Controllers\PerfilController;
use App\Controllers\ProductoController;
use App\Controllers\PwaController;

// App instalable (PWA): sin sesión, el navegador los pide solo
$router->get('/manifest.webmanifest', [PwaController::class, 'manifest']);
$router->get('/sw.js', [PwaController::class, 'serviceWorker']);
$router->get('/offline', [PwaController::class, 'offline']);

// Acceso
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/login/google', [AuthController::class, 'loginGoogle'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

// Anotador de fechas
$router->get('/', [InicioController::class, 'index'], ['auth']);
$router->get('/fechas', [InicioController::class, 'fechas'], ['auth']);
$router->get('/fechas/productos', [InicioController::class, 'productos'], ['auth']);
$router->post('/fechas', [InicioController::class, 'guardar'], ['auth']);
$router->post('/fechas/{id}/retirar', [InicioController::class, 'retirar'], ['auth']);
$router->post('/fechas/{id}/eliminar', [InicioController::class, 'eliminar'], ['auth']);
$router->post('/locales/detectar', [InicioController::class, 'detectar'], ['auth']);
$router->get('/locales/crear', [LocalController::class, 'create'], ['auth']);
$router->post('/locales', [LocalController::class, 'store'], ['auth']);
$router->post('/locales/ubicacion', [LocalController::class, 'ubicacion'], ['auth']);

// Avisos (notificaciones)
$router->get('/avisos/clave', [AvisoController::class, 'clave'], ['auth']);
$router->get('/avisos/resumen', [AvisoController::class, 'resumen'], ['auth']);
$router->post('/avisos/suscribir', [AvisoController::class, 'suscribir'], ['auth']);
$router->post('/avisos/desuscribir', [AvisoController::class, 'desuscribir'], ['auth']);
$router->post('/avisos/probar', [AvisoController::class, 'probar'], ['auth']);

// Mi cuenta
$router->get('/perfil', [PerfilController::class, 'show'], ['auth']);
$router->post('/perfil/datos', [PerfilController::class, 'datos'], ['auth']);
$router->post('/perfil/email', [PerfilController::class, 'email'], ['auth']);
$router->post('/perfil/foto', [PerfilController::class, 'foto'], ['auth']);
$router->post('/perfil/foto/quitar', [PerfilController::class, 'quitarFoto'], ['auth']);
$router->post('/perfil/password', [PerfilController::class, 'password'], ['auth']);
$router->post('/perfil/sesiones', [PerfilController::class, 'cerrarSesiones'], ['auth']);
$router->post('/perfil/avisos', [PerfilController::class, 'avisos'], ['auth']);
$router->get('/usuarios/{id}/foto', [PerfilController::class, 'verFoto'], ['auth']);

// Productos (buscar, escanear, ficha, alta rápida)
$router->get('/productos', [ProductoController::class, 'index'], ['auth']);
$router->get('/productos/buscar', [ProductoController::class, 'buscar'], ['auth']);
$router->get('/productos/codigo', [ProductoController::class, 'porCodigo'], ['auth']);
$router->get('/productos/sin-codigo', [ProductoController::class, 'sinCodigo'], ['auth']);
$router->post('/productos/{id}/codigo', [ProductoController::class, 'asociarCodigo'], ['auth']);
$router->get('/productos/crear', [ProductoController::class, 'create'], ['auth']);
$router->post('/productos', [ProductoController::class, 'store'], ['auth']);
$router->get('/productos/{id}', [ProductoController::class, 'show'], ['auth']);
$router->get('/productos/{id}/imagen', [ProductoController::class, 'imagen'], ['auth']);

// Gestión (admin)
$admin = ['auth', 'admin'];
$router->get('/gestion', [PerfilController::class, 'gestion'], $admin);
$router->get('/admin', [PerfilController::class, 'gestion'], $admin);
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

$router->get('/admin/usuarios', [UsuarioController::class, 'index'], $admin);
$router->get('/admin/usuarios/crear', [UsuarioController::class, 'create'], $admin);
$router->post('/admin/usuarios', [UsuarioController::class, 'store'], $admin);
$router->get('/admin/usuarios/{id}/editar', [UsuarioController::class, 'edit'], $admin);
$router->post('/admin/usuarios/{id}', [UsuarioController::class, 'update'], $admin);
$router->post('/admin/usuarios/{id}/estado', [UsuarioController::class, 'toggle'], $admin);
$router->post('/admin/usuarios/{id}/eliminar', [UsuarioController::class, 'eliminar'], $admin);
