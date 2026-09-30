<?php
/**
 * Genera los íconos PNG de la app (PWA) en public/assets/icons/.
 *
 *   c:\xampp\php\php.exe database/generar-iconos.php
 *
 * Dibuja el mismo logo que assets/img/logo.svg (fondo violeta con una "J").
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!extension_loaded('gd')) {
    fwrite(STDERR, "Falta la extensión GD. Usá el PHP de XAMPP: c:\\xampp\\php\\php.exe\n");
    exit(1);
}

$destino = dirname(__DIR__) . '/public/assets/icons';
if (!is_dir($destino)) {
    mkdir($destino, 0775, true);
}

/**
 * @param bool $sangrado true = fondo a todo el cuadrado (maskable / Apple); false = esquinas redondeadas
 */
function icono(int $tamano, bool $sangrado, float $escalaLogo): GdImage
{
    $s = $tamano * 4; // se dibuja al cuádruple y se achica: bordes suaves
    $img = imagecreatetruecolor($s, $s);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
    imagealphablending($img, true);

    // Fondo: degradé diagonal #8B5CF6 → #6D28D9.
    $radio = $sangrado ? 0 : (int) ($s * 0.25);
    for ($y = 0; $y < $s; $y++) {
        for ($x = 0; $x < $s; $x++) {
            if ($radio > 0) {
                $cx = $x < $radio ? $radio : ($x >= $s - $radio ? $s - $radio - 1 : $x);
                $cy = $y < $radio ? $radio : ($y >= $s - $radio ? $s - $radio - 1 : $y);
                if (($x - $cx) ** 2 + ($y - $cy) ** 2 > $radio ** 2) {
                    continue;
                }
            }
            $t = ($x + $y) / (2 * $s);
            imagesetpixel($img, $x, $y, imagecolorallocate(
                $img,
                (int) (0x8B + (0x6D - 0x8B) * $t),
                (int) (0x5C + (0x28 - 0x5C) * $t),
                (int) (0xF6 + (0xD9 - 0xF6) * $t)
            ));
        }
    }

    // Logo en coordenadas de 64×64 (igual que el SVG), centrado y escalado.
    $u = $s / 64 * $escalaLogo;
    $off = ($s - 64 * $u) / 2;
    $punto = fn (float $px, float $py) => [$off + $px * $u, $off + $py * $u];
    $blanco = imagecolorallocate($img, 255, 255, 255);
    $grosor = 7 * $u;

    $pincel = function (float $px, float $py) use ($img, $punto, $blanco, $grosor): void {
        [$x, $y] = $punto($px, $py);
        imagefilledellipse($img, (int) $x, (int) $y, (int) $grosor, (int) $grosor, $blanco);
    };

    for ($py = 15; $py <= 38; $py += 0.25) {          // palo de la J
        $pincel(38, $py);
    }
    for ($a = 0; $a <= 180; $a += 1) {                 // curva de la J (centro 28,38 radio 10)
        $pincel(28 + 10 * cos(deg2rad($a)), 38 + 10 * sin(deg2rad($a)));
    }
    [$dx, $dy] = $punto(47, 17);                       // punto
    imagefilledellipse($img, (int) $dx, (int) $dy, (int) (9 * $u), (int) (9 * $u), imagecolorallocate($img, 0xDD, 0xD6, 0xFE));

    $final = imagecreatetruecolor($tamano, $tamano);
    imagealphablending($final, false);
    imagesavealpha($final, true);
    imagecopyresampled($final, $img, 0, 0, 0, 0, $tamano, $tamano, $s, $s);
    return $final;
}

$iconos = [
    'icon-192.png'          => [192, false, 1.0],
    'icon-512.png'          => [512, false, 1.0],
    'icon-maskable-512.png' => [512, true, 0.75], // zona segura: Android recorta en círculo
    'apple-touch-icon.png'  => [180, true, 0.9],  // iOS redondea solo
];

foreach ($iconos as $archivo => [$tamano, $sangrado, $escala]) {
    imagepng(icono($tamano, $sangrado, $escala), "{$destino}/{$archivo}", 9);
    echo "  OK  public/assets/icons/{$archivo}\n";
}
