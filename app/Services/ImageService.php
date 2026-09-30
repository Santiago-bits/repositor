<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Guarda imágenes subidas: valida el contenido real, corrige la orientación,
 * achica y re-codifica a JPEG (así también se borran los datos EXIF, incluido el GPS).
 * Los archivos quedan en storage/, fuera del alcance del navegador.
 */
final class ImageService
{
    public const MAX_BYTES = 15 * 1024 * 1024;
    private const MAX_PIXELES = 40_000_000;
    private const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

    /** true si el campo de archivo vino vacío (no se eligió imagen). */
    public static function sinArchivo(?array $file): bool
    {
        return $file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;
    }

    /**
     * @return string ruta relativa a storage/ (ej: "productos/2026/09/ab12….jpg")
     * @throws RuntimeException con un mensaje apto para mostrar al usuario
     */
    public static function guardar(array $file, string $carpeta, int $maxLado = 1600, int $calidad = 80): string
    {
        return self::procesar($file, $carpeta, $maxLado, $calidad)['path'];
    }

    /**
     * Igual que guardar(), pero devuelve también los metadatos para registrar en la base.
     * $sub agrega carpetas después de año/mes (ej: "local_15/producto_203").
     *
     * @return array{path: string, mime: string, bytes: int, ancho: int, alto: int}
     * @throws RuntimeException con un mensaje apto para mostrar al usuario
     */
    public static function procesar(array $file, string $carpeta, int $maxLado = 1600, int $calidad = 80, string $sub = ''): array
    {
        if (!extension_loaded('gd')) {
            \App\Core\ErrorHandler::log(new RuntimeException('Falta la extensión GD de PHP (php.ini: extension=gd).'));
            throw new RuntimeException('El servidor no puede procesar imágenes en este momento. Avisale al administrador.');
        }
        self::validarSubida($file);

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $info = @getimagesize($file['tmp_name']);
        if (!in_array($mime, self::TIPOS, true) || $info === false) {
            throw new RuntimeException('El archivo no es una imagen válida. Usá una foto JPG, PNG o WEBP.');
        }
        [$ancho, $alto] = $info;
        if ($ancho * $alto > self::MAX_PIXELES) {
            throw new RuntimeException('La imagen tiene demasiada resolución. Probá con una foto más chica.');
        }

        $origen = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        };
        if ($origen === false) {
            throw new RuntimeException('No se pudo leer la imagen. Probá con otra foto.');
        }

        if ($mime === 'image/jpeg') {
            $origen = self::corregirOrientacion($origen, $file['tmp_name']);
        }

        $destino = self::redimensionar($origen, $maxLado);

        $sub = trim(preg_replace('/[^a-z0-9_\/]/i', '', $sub), '/');
        $relativa = trim($carpeta, '/') . '/' . date('Y/m') . ($sub !== '' ? "/{$sub}" : '') . '/foto_' . bin2hex(random_bytes(12)) . '.jpg';
        $absoluta = self::ruta($relativa);
        if (!is_dir(dirname($absoluta)) && !mkdir(dirname($absoluta), 0775, true) && !is_dir(dirname($absoluta))) {
            throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
        }
        if (!imagejpeg($destino, $absoluta, $calidad)) {
            throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
        }

        return [
            'path'  => $relativa,
            'mime'  => 'image/jpeg',
            'bytes' => (int) filesize($absoluta),
            'ancho' => imagesx($destino),
            'alto'  => imagesy($destino),
        ];
    }

    public static function eliminar(?string $relativa): void
    {
        if ($relativa) {
            $ruta = self::ruta($relativa);
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }
    }

    /** Ruta absoluta dentro de storage/ (rechaza rutas con "..") */
    public static function ruta(string $relativa): string
    {
        if (str_contains($relativa, '..')) {
            throw new RuntimeException('Ruta de archivo inválida.');
        }
        return BASE_PATH . '/storage/' . ltrim($relativa, '/');
    }

    /** Envía la imagen al navegador (solo después de verificar permisos). */
    public static function enviar(string $relativa): never
    {
        $ruta = self::ruta($relativa);
        if (!is_file($ruta)) {
            \App\Core\View::error(404, 'La imagen ya no está disponible.');
        }
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: private, max-age=604800');
        readfile($ruta);
        exit;
    }

    private static function validarSubida(array $file): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $mb = (int) (self::MAX_BYTES / 1024 / 1024);

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException("La imagen es demasiado pesada (máximo {$mb} MB).");
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new RuntimeException('No se pudo subir la imagen. Probá de nuevo.');
        }
    }

    /** Las fotos de celular vienen "acostadas" con una marca EXIF de rotación. */
    private static function corregirOrientacion(\GdImage $img, string $archivo): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($archivo);
        $angulo = match ((int) ($exif['Orientation'] ?? 1)) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };
        return $angulo === 0 ? $img : (imagerotate($img, $angulo, 0) ?: $img);
    }

    private static function redimensionar(\GdImage $origen, int $maxLado): \GdImage
    {
        $w = imagesx($origen);
        $h = imagesy($origen);
        $escala = min(1, $maxLado / max($w, $h));
        $nw = max(1, (int) round($w * $escala));
        $nh = max(1, (int) round($h * $escala));

        $destino = imagecreatetruecolor($nw, $nh);
        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255)); // fondo blanco para PNG transparentes
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $destino;
    }
}
