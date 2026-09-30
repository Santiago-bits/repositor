<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'app'): void
    {
        $content = self::capture($view, $data);
        echo $layout === null
            ? $content
            : self::capture('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function partial(string $name, array $data = []): string
    {
        return self::capture('partials/' . $name, $data);
    }

    public static function error(int $code, ?string $message = null): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($code);
        }

        $message ??= match ($code) {
            401     => 'Tu sesión expiró. Volvé a ingresar.',
            403     => 'No tenés permiso para ver esta sección.',
            404     => 'No encontramos la página que buscás.',
            405     => 'Esa acción no está permitida.',
            419     => 'La página estuvo abierta mucho tiempo. Recargala y volvé a intentarlo.',
            default => 'Ocurrió un error inesperado. Probá de nuevo en unos segundos.',
        };

        if (Request::wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            echo self::capture('errors/error', ['code' => $code, 'message' => $message]);
        } catch (Throwable) {
            echo e($message);
        }
        exit;
    }

    private static function capture(string $__view, array $__data): string
    {
        $__file = BASE_PATH . '/app/Views/' . $__view . '.php';
        if (!is_file($__file)) {
            throw new RuntimeException("Vista no encontrada: {$__view}");
        }

        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            require $__file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
