<?php
declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', APP_DEBUG ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(function (int $no, string $msg, string $file, int $line): bool {
            if (!(error_reporting() & $no)) {
                return false;
            }
            throw new ErrorException($msg, 0, $no, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(Throwable $e): void
    {
        self::log($e);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
            exit(1);
        }

        $message = APP_DEBUG
            ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
            : null;

        View::error(500, $message);
    }

    public static function log(Throwable $e): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf(
            "[%s] %s: %s en %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
