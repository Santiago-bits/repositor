<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Saca latitud y longitud de lo que uno copia de Google Maps:
 *  - un link (también los cortos maps.app.goo.gl),
 *  - coordenadas sueltas ("-31.3957, -58.0163"),
 *  - un Plus Code, completo o corto ("JX3M+PF Concordia").
 */
final class UbicacionService
{
    /** Si no hay locales con ubicación para tomar de referencia: centro de Concordia. */
    private const REFERENCIA = [-31.3930, -58.0209];

    /** Solo se siguen redirecciones de Google (nunca se consulta otra dirección). */
    private const HOSTS = '/^(maps\.app\.goo\.gl|goo\.gl|(www\.|maps\.)?google\.[a-z.]+)$/i';

    private const OLC_ALFABETO = '23456789CFGHJMPQRVWX';
    private const OLC_RESOLUCIONES = [20.0, 1.0, 0.05, 0.0025, 0.000125];

    /** @return array{lat: float, lng: float}|null */
    public static function resolver(string $texto): ?array
    {
        $texto = trim($texto);
        if ($texto === '') {
            return null;
        }

        if (preg_match('~https?://\S+~i', $texto, $m)) {
            $url = self::expandir($m[0]);
            // Si el link no trae coordenadas, a veces trae el Plus Code ("/place/JX3M%2BPF,+Concordia").
            return self::deUrl($url) ?? self::dePlusCode(rawurldecode(str_replace('+', ' ', $url)));
        }

        return self::deCoordenadas($texto) ?? self::dePlusCode($texto);
    }

    // ── Links ─────────────────────────────────────────────────────

    /** Sigue las redirecciones de un link corto (hasta 5 saltos) y devuelve la URL final. */
    private static function expandir(string $url): string
    {
        for ($i = 0; $i < 5; $i++) {
            $host = (string) parse_url($url, PHP_URL_HOST);
            if (!preg_match(self::HOSTS, $host) || self::deUrl($url) !== null) {
                return $url;
            }
            $destino = self::redireccion($url);
            if ($destino === null) {
                return $url;
            }
            $url = $destino;
        }
        return $url;
    }

    private static function redireccion(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY         => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS | CURLPROTO_HTTP,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (JACOB)',
            ]);
            curl_exec($ch);
            $destino = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            curl_close($ch);
            return is_string($destino) && $destino !== '' ? $destino : null;
        }

        $contexto = stream_context_create(['http' => ['method' => 'HEAD', 'follow_location' => 0, 'timeout' => 8]]);
        foreach ((array) @get_headers($url, true, $contexto) as $clave => $valor) {
            if (strcasecmp((string) $clave, 'Location') === 0) {
                return is_array($valor) ? (string) reset($valor) : (string) $valor;
            }
        }
        return null;
    }

    private static function deUrl(string $url): ?array
    {
        $url = rawurldecode($url);
        $patrones = [
            '~!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)~',                      // el pin exacto
            '~[?&](?:q|query|ll|destination|center)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)~',
            '~/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)~',                          // centro del mapa
            '~/search/(-?\d+(?:\.\d+)?),\s*\+?(-?\d+(?:\.\d+)?)~',
        ];
        foreach ($patrones as $p) {
            if (preg_match($p, $url, $m) && ($c = self::valida((float) $m[1], (float) $m[2])) !== null) {
                return $c;
            }
        }
        return null;
    }

    private static function deCoordenadas(string $texto): ?array
    {
        if (preg_match('/^\s*(-?\d{1,2}(?:\.\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $texto, $m)) {
            return self::valida((float) $m[1], (float) $m[2]);
        }
        return null;
    }

    private static function valida(float $lat, float $lng): ?array
    {
        if (abs($lat) > 90 || abs($lng) > 180 || ($lat == 0.0 && $lng == 0.0)) {
            return null;
        }
        return ['lat' => round($lat, 7), 'lng' => round($lng, 7)];
    }

    // ── Plus Codes (Open Location Code) ───────────────────────────

    private static function dePlusCode(string $texto): ?array
    {
        $letras = self::OLC_ALFABETO;
        if (!preg_match("/(?<![{$letras}])([{$letras}0]{2,8}\\+[{$letras}]{0,7})(?![{$letras}])/", strtoupper($texto), $m)) {
            return null;
        }
        $codigo = $m[1];
        $antes = strpos($codigo, '+');

        if ($antes === 8) {
            $area = self::olcDecodificar($codigo);
        } elseif ($antes >= 2 && $antes <= 6 && $antes % 2 === 0) {
            $area = self::olcRecuperar($codigo, self::referencia());
        } else {
            return null;
        }
        return $area !== null ? self::valida($area[0], $area[1]) : null;
    }

    /** Centro del área de un código completo (las cifras después de las 10 primeras se ignoran: ~14 m). */
    private static function olcDecodificar(string $codigo): ?array
    {
        $digitos = str_replace(['+', '0'], '', $codigo);
        $pares = intdiv(min(strlen($digitos), 10), 2);
        if ($pares === 0) {
            return null;
        }
        $lat = -90.0;
        $lng = -180.0;
        for ($i = 0; $i < $pares; $i++) {
            $a = strpos(self::OLC_ALFABETO, $digitos[$i * 2]);
            $b = strpos(self::OLC_ALFABETO, $digitos[$i * 2 + 1]);
            if ($a === false || $b === false) {
                return null;
            }
            $lat += $a * self::OLC_RESOLUCIONES[$i];
            $lng += $b * self::OLC_RESOLUCIONES[$i];
        }
        $mitad = self::OLC_RESOLUCIONES[$pares - 1] / 2;
        return [$lat + $mitad, $lng + $mitad];
    }

    /** Código corto ("JX3M+PF"): se completa con la zona de referencia (algoritmo oficial de recuperación). */
    private static function olcRecuperar(string $corto, array $ref): ?array
    {
        $relleno = 8 - strpos($corto, '+');
        $resolucion = 20 ** (2 - $relleno / 2);
        $mitad = $resolucion / 2;

        $area = self::olcDecodificar(substr(self::olcCodificar($ref[0], $ref[1]), 0, $relleno) . $corto);
        if ($area === null) {
            return null;
        }
        [$lat, $lng] = $area;

        if ($ref[0] + $mitad < $lat && $lat - $resolucion >= -90) {
            $lat -= $resolucion;
        } elseif ($ref[0] - $mitad > $lat && $lat + $resolucion <= 90) {
            $lat += $resolucion;
        }
        if ($ref[1] + $mitad < $lng) {
            $lng -= $resolucion;
        } elseif ($ref[1] - $mitad > $lng) {
            $lng += $resolucion;
        }
        return [$lat, $lng];
    }

    private static function olcCodificar(float $lat, float $lng): string
    {
        $lat = min(max($lat, -90), 89.9999999) + 90;
        $lng = fmod($lng + 180 + 360, 360);
        $codigo = '';
        foreach (self::OLC_RESOLUCIONES as $r) {
            $a = (int) floor($lat / $r);
            $b = (int) floor($lng / $r);
            $codigo .= self::OLC_ALFABETO[$a] . self::OLC_ALFABETO[$b];
            $lat -= $a * $r;
            $lng -= $b * $r;
        }
        return substr($codigo, 0, 8) . '+' . substr($codigo, 8);
    }

    /** Referencia para los códigos cortos: el promedio de los locales que ya tienen ubicación. */
    private static function referencia(): array
    {
        $fila = Database::connection()->query(
            'SELECT AVG(latitud) AS lat, AVG(longitud) AS lng FROM locales WHERE latitud IS NOT NULL AND deleted_at IS NULL'
        )->fetch();
        return $fila && $fila['lat'] !== null ? [(float) $fila['lat'], (float) $fila['lng']] : self::REFERENCIA;
    }
}
