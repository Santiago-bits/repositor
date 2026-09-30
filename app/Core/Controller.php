<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Vuelve al formulario con los errores y lo que se había escrito. */
    protected function backWithErrors(array $errors, array $old, string $fallback = '/'): never
    {
        Session::flash('errors', $errors);
        Session::flash('old', $old);
        flash('error', 'Revisá los campos marcados.');
        back($fallback);
    }

    protected function notFoundUnless(mixed $value): mixed
    {
        return $value ?: View::error(404);
    }
}
