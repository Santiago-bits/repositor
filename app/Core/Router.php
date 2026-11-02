<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    /** @param array{0: class-string, 1: string} $action */
    public function get(string $path, array $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, array $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    private function add(string $method, string $path, array $action, array $middleware): void
    {
        $path = '/' . trim($path, '/');
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $path) . '$#';
        $this->routes[] = compact('method', 'pattern', 'action', 'middleware');
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();
        $pathExists = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }
            $pathExists = true;
            if ($route['method'] !== $method) {
                continue;
            }

            if ($method === 'POST') {
                $this->verifyCsrf();
            }
            $this->runMiddleware($route['middleware']);

            $params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            [$class, $fn] = $route['action'];
            (new $class())->$fn(...array_values($params));
            return;
        }

        View::error($pathExists ? 405 : 404);
    }

    private function verifyCsrf(): void
    {
        // Si el envío supera post_max_size, PHP descarta todo el formulario (token incluido).
        if ($_POST === [] && $_FILES === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
            && str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) {
            if (Request::wantsJson()) {
                View::error(413, 'El archivo es demasiado pesado. Probá con una foto más liviana.');
            }
            flash('error', 'El archivo es demasiado pesado. Probá con una foto más liviana.');
            back();
        }

        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (Csrf::verify($token)) {
            return;
        }
        if (Request::wantsJson()) {
            // 403 y no 419: Apache no conoce el 419 y lo convierte en 500.
            View::error(403, 'La página estuvo abierta mucho tiempo. Recargala y volvé a intentarlo.');
        }
        flash('error', 'La página estuvo abierta mucho tiempo. Volvé a intentarlo.');
        back();
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $name) {
            match ($name) {
                'auth'  => $this->requireAuth(),
                'guest' => Auth::check() ? redirect('/') : null,
                'admin' => Auth::isAdmin() ? null : View::error(403),
            };
        }
    }

    private function requireAuth(): void
    {
        if (Auth::check()) {
            return;
        }
        if (Request::wantsJson()) {
            View::error(401, 'Tu sesión expiró. Volvé a ingresar.');
        }
        if (Request::method() === 'GET') {
            Session::set('intended', Request::path());
        }
        redirect('/login');
    }
}
