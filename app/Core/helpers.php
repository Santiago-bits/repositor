<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** URL de un archivo de public/ con versión para evitar caché vieja. */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/' . ltrim($path, '/');
    return url($path) . '?v=' . (is_file($file) ? filemtime($file) : '0');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function back(string $fallback = '/'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $sameHost = $referer !== '' && parse_url($referer, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? null);
    header('Location: ' . ($sameHost ? $referer : url($fallback)));
    exit;
}

function flash(string $type, string $text): void
{
    Session::pushFlash('messages', ['type' => $type, 'text' => $text]);
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::old();
    return array_key_exists($key, $old) ? $old[$key] : $default;
}

function has_old(): bool
{
    return Session::old() !== [];
}

function error_for(string $key): ?string
{
    return Session::errors()[$key] ?? null;
}

/** Clase de Bootstrap para un campo con error. */
function invalid(string $key): string
{
    return error_for($key) ? ' is-invalid' : '';
}

function field_error(string $key): string
{
    $msg = error_for($key);
    return $msg ? '<div class="invalid-feedback d-block">' . e($msg) . '</div>' : '';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function auth(): ?array
{
    return Auth::user();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

function partial(string $name, array $data = []): string
{
    return View::partial($name, $data);
}

/** "active" si la ruta actual coincide con $path. */
function active(string $path, bool $exact = false): string
{
    $current = Request::path();
    $match = $exact || $path === '/'
        ? $current === $path
        : $current === $path || str_starts_with($current, rtrim($path, '/') . '/');
    return $match ? 'active' : '';
}

function initials(?array $user): string
{
    if (!$user) {
        return '';
    }
    return mb_strtoupper(mb_substr($user['nombre'], 0, 1) . mb_substr($user['apellido'], 0, 1));
}

function fecha(?string $value, string $format = 'd/m/Y'): string
{
    return $value ? date($format, strtotime($value)) : '—';
}

/** "$1.200" o "$1.200,50" (formato argentino, decimales solo si hacen falta). */
function precio(mixed $valor): string
{
    $n = (float) $valor;
    return '$' . number_format($n, fmod($n, 1.0) != 0.0 ? 2 : 0, ',', '.');
}

/** true si $valor es una fecha real en formato Y-m-d (rechaza 31/02, etc.). */
function fecha_valida(string $valor): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $valor);
    return $d !== false && $d->format('Y-m-d') === $valor;
}

/** "42 min" o "1 h 05 min" entre dos fechas (sin $fin: hasta ahora). */
function duracion(?string $inicio, ?string $fin = null): string
{
    if (!$inicio) {
        return '—';
    }
    $minutos = max(0, intdiv(strtotime($fin ?? 'now') - strtotime($inicio), 60));
    return $minutos < 60 ? "{$minutos} min" : sprintf('%d h %02d min', intdiv($minutos, 60), $minutos % 60);
}

function dia_semana(?int $timestamp = null): string
{
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return $dias[(int) date('w', $timestamp ?? time())];
}

/** Navegación principal (barra inferior en celular, menú lateral en PC). */
function nav_principal(): array
{
    return [
        ['/', 'bi-house-door', 'Inicio'],
        ['/productos', 'bi-box-seam', 'Productos'],
        ['/tareas', 'bi-list-check', 'Tareas'],
        ['/historial', 'bi-clock-history', 'Historial'],
        ['/perfil', 'bi-person-circle', 'Perfil'],
    ];
}

function nav_admin(): array
{
    return [
        ['/admin', 'bi-speedometer2', 'Resumen'],
        ['/admin/relevamientos', 'bi-clipboard-check', 'Relevamientos'],
        ['/reportes', 'bi-file-earmark-bar-graph', 'Reportes'],
        ['/admin/fotos', 'bi-images', 'Fotos'],
        ['/admin/locales', 'bi-shop', 'Locales'],
        ['/admin/productos', 'bi-box-seam', 'Productos'],
        ['/admin/categorias', 'bi-tags', 'Categorías'],
        ['/admin/promociones', 'bi-megaphone', 'Promociones'],
        ['/admin/tareas', 'bi-list-check', 'Tareas'],
        ['/admin/usuarios', 'bi-people', 'Usuarios'],
        ['/admin/configuracion', 'bi-gear', 'Configuración'],
    ];
}
