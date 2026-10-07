<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Categoria;
use App\Models\Producto;
use RuntimeException;

/**
 * Importa el maestro de artículos de Chess (JSON con "divisiones" → artículos con agrupaciones y precios).
 * El artículo se identifica por idArticulo: volver a subir el archivo actualiza nombres y precios sin duplicar.
 */
final class ChessImportService
{
    /** División de Chess → categoría de la app (así la lista para el vendedor sale agrupada igual). */
    private const DIVISIONES = [
        'CERVEZAS'            => 'Cervezas',
        'GASEOSAS'            => 'Gaseosas',
        'AGUAS'               => 'Aguas',
        'BEBIDAS SABORIZADAS' => 'Aguas saborizadas',
        'ISOTONICAS'          => 'Isotónicas',
        'BEB ENERGIZANTES'    => 'Energizantes',
        'VINOS'               => 'Vinos',
        'SIDRAS'              => 'Sidras',
        'SPIRITS ADYACENCIAS' => 'Gin y sidras',
    ];

    /** En estas divisiones el nombre sale de la descripción del artículo (la marca no alcanza: "Novecento" tiene varios). */
    private const NOMBRE_DESDE_DESCRIPCION = ['VINOS', 'SIDRAS', 'SPIRITS ADYACENCIAS'];

    /** Sabores que no agregan nada al nombre. */
    private const SABORES_IGNORADOS = ['BLANCA', 'TRADICIONAL', 'LAGER', 'COLA', 'APERITIVO', 'SIDRA', 'RED BULL',
                                       'ROCKSTAR', 'LIMA-LIMON', 'AGUA DE MESA'];

    /** Familias que no agregan nada al nombre. */
    private const FAMILIAS_IGNORADAS = ['REGULAR', 'LIGHT', 'BAJAS CALORIAS', 'SIN ALCOHOL'];

    private const PALABRAS = [
        'AGUA SIN GAS' => 'sin gas', 'AGUA CON GAS' => 'con gas', 'SIN AZUCAR' => 'sin azúcar',
        'TONICA' => 'Tónica', 'LIMON' => 'Limón', 'NESTLE' => 'Nestlé', 'H2OH' => 'H2Oh', 'IPA' => 'IPA',
        'MONTANA' => 'Montaña', 'ANOS' => 'Años', 'ROSE' => 'Rosé', 'CAJON' => 'Cajón',
    ];

    /** Marcas que conviene escribir distinto. */
    private const MARCAS = [
        '7 UP' => '7Up', '7 UP FREE' => '7Up Free', 'H2OH' => 'H2Oh', 'QUILMES 0.0%' => 'Quilmes 0.0',
        'STELLA ARTOIS 0.0%' => 'Stella Artois 0.0', 'STOUT QUILMES' => 'Quilmes Stout',
        'NESTLE PUREZA VITAL' => 'Nestlé Pureza Vital',
    ];

    /**
     * Lo que se borra de la descripción de Chess para dejar solo el nombre
     * ("NOVECENTO MALBEC OW X6 750CC" → "Novecento Malbec"). Los años ("Sidra 1930") se respetan.
     */
    private const RUIDO = '/\b(OW|CAN|PET|BOT|RET|BIB|CART|NF|PM|IMP|EXP|SMK|HC|CMQ|AR|LAT)\b|\bX\d+\b|\b\d+X\d+\b'
        . '|\b\d+(?:[.,]\d+)?\s*(?:CC|LTS|L|ML)\b|\b(?!19\d\d\b|20\d\d\b)\d{3,4}\b|[*#]|\s-\s/u';

    /** @return array{creados: int, actualizados: int, sin_cambios: int, desactivados: int, errores: array<string, string>} */
    public static function importar(array $json): array
    {
        if (!isset($json['divisiones']) || !is_array($json['divisiones'])) {
            throw new RuntimeException('Ese JSON no es el maestro de artículos de Chess (falta "divisiones").');
        }

        $resultado = ['creados' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'desactivados' => 0, 'errores' => []];
        $categorias = [];

        Database::transaction(function () use ($json, &$resultado, &$categorias): void {
            foreach ($json['divisiones'] as $division => $articulos) {
                foreach ((array) $articulos as $a) {
                    if (!is_array($a) || empty($a['idArticulo']) || empty($a['desArticulo'])) {
                        continue;
                    }
                    try {
                        $resultado[self::guardar((string) $division, $a, $categorias)]++;
                    } catch (RuntimeException $e) {
                        $resultado['errores'][(string) $a['desArticulo']] = $e->getMessage();
                    }
                }
            }
        });

        return $resultado;
    }

    /** @return 'creados'|'actualizados'|'sin_cambios'|'desactivados' */
    private static function guardar(string $division, array $a, array &$categorias): string
    {
        $codigo = (string) (int) $a['idArticulo'];
        $existente = Producto::findByCodigoInterno($codigo);

        // Artículo dado de baja en Chess: se desactiva acá (no se borra, por el historial).
        if (!empty($a['anulado'])) {
            if ($existente !== null && $existente['activo']) {
                Producto::setActivo((int) $existente['id'], false);
                return 'desactivados';
            }
            return 'sin_cambios';
        }

        $ag = (array) ($a['agrupaciones'] ?? []);
        [$presentacion, $unidad] = self::presentacion((string) ($ag['CALIBRE'] ?? ''), (string) $a['desArticulo']);
        $lista2 = (array) ($a['precios']['lista_2'] ?? []);
        $lista1 = (array) ($a['precios']['lista_1'] ?? []);

        $datos = [
            'nombre'             => mb_substr(self::nombre($division, $a, $ag), 0, 150),
            'marca'              => self::marca((string) ($ag['MARCA'] ?? '')),
            'presentacion'       => $presentacion,
            'categoria_id'       => self::categoria(self::DIVISIONES[$division] ?? self::titulo($division), $categorias),
            'unidad_medida'      => $unidad,
            'descripcion'        => mb_substr(trim((string) $a['desArticulo']), 0, 1000),
            'codigo_interno'     => $codigo,
            'unidades_bulto'     => isset($a['unidadesBulto']) ? (int) $a['unidadesBulto'] : null,
            'presentacion_bulto' => !empty($lista2['presentacion']) ? mb_substr(self::titulo((string) $lista2['presentacion']), 0, 30) : null,
            'precio_unidad'      => self::precio($lista2['precio_unidad'] ?? null),
            'precio_bulto'       => self::precio($lista2['precio_final'] ?? null),
            'precio_base_unidad' => self::precio($lista1['precio_unidad'] ?? null),
            'precio_base_bulto'  => self::precio($lista1['precio_final'] ?? null),
            'precio_vigente'     => self::fecha($lista2['vigente_desde'] ?? $lista1['vigente_desde'] ?? null),
        ];

        if ($existente === null) {
            Producto::guardarDesdeChess(null, $datos);
            return 'creados';
        }

        foreach ($datos as $campo => $valor) {
            $antes = $existente[$campo] ?? null;
            $igual = is_numeric($valor) && is_numeric($antes)
                ? (float) $valor === (float) $antes
                : (string) ($antes ?? '') === (string) ($valor ?? '');
            if (!$igual) {
                Producto::guardarDesdeChess((int) $existente['id'], $datos);
                return 'actualizados';
            }
        }
        return 'sin_cambios';
    }

    /** "Paso de los Toros Pomelo sin azúcar", "Andes Origen Roja", "Novecento Malbec". */
    private static function nombre(string $division, array $a, array $ag): string
    {
        if (in_array($division, self::NOMBRE_DESDE_DESCRIPCION, true) || empty($ag['MARCA'])) {
            $limpio = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace(self::RUIDO, ' ', ' ' . strtoupper((string) $a['desArticulo']) . ' ')));
            return self::titulo($limpio !== '' ? $limpio : (string) $a['desArticulo']);
        }

        $nombre = (string) self::marca((string) $ag['MARCA']);
        $agregar = function (string $parte) use (&$nombre): void {
            $parte = trim($parte);
            if ($parte !== '' && mb_stripos(self::sinTildes($nombre), self::sinTildes($parte)) === false) {
                $nombre .= ' ' . $parte;
            }
        };

        $sabor = strtoupper(trim((string) preg_replace('/\(\d+\)/', '', (string) ($ag['SABOR'] ?? ''))));
        if ($sabor !== '' && !in_array($sabor, self::SABORES_IGNORADOS, true)) {
            $agregar(self::PALABRAS[$sabor] ?? self::titulo($sabor));
        }

        $familia = strtoupper(trim((string) ($ag['FAMILIA'] ?? '')));
        if ($familia !== '' && !in_array($familia, self::FAMILIAS_IGNORADAS, true)
            && !($familia === 'SIN AZUCAR' && mb_stripos($nombre, 'free') !== false)) {
            $agregar(self::PALABRAS[$familia] ?? self::titulo($familia));
        }

        return $nombre;
    }

    /**
     * Del calibre de Chess a algo legible: "1000 CC VIDRIO" → 1 L, "473 CC LATAS" → 473 cc lata,
     * "BARRIL 20L" → Barril 20 L, "2000 RECO" → 2 L retornable.
     *
     * @return array{0: ?string, 1: ?string} [presentación, unidad de medida]
     */
    private static function presentacion(string $calibre, string $descripcion): array
    {
        $c = strtoupper($calibre);
        if (!preg_match('/(\d+(?:[.,]\d+)?)\s*(LTS|L|CC)?/', $c, $m)) {
            return [$calibre !== '' ? mb_substr(self::titulo($calibre), 0, 40) : null, null];
        }

        $numero = (float) str_replace(',', '.', $m[1]);
        $enLitros = in_array($m[2] ?? '', ['L', 'LTS'], true);
        $litros = $enLitros ? $numero : ($numero >= 1000 ? $numero / 1000 : null);

        if ($litros !== null) {
            $texto = rtrim(rtrim(number_format($litros, 2, ',', ''), '0'), ',') . ' L';
            $unidad = 'l';
        } else {
            $texto = (int) $numero . ' cc';
            $unidad = 'cc';
        }

        $d = ' ' . strtoupper($descripcion) . ' ';
        $texto = match (true) {
            str_contains($c, 'BARRIL')                                         => 'Barril ' . $texto,
            str_contains($c, 'BIDON') || str_contains($d, ' BIDON ')           => 'Bidón ' . $texto,
            str_contains($c, 'RECO')                                           => $texto . ' retornable',
            str_contains($d, ' BIB ')                                          => $texto . ' bag in box',
            str_contains($c, 'LATA') || preg_match('/ (CAN|LAT) /', $d) === 1  => $texto . ' lata',
            default                                                            => $texto,
        };
        return [$texto, $unidad];
    }

    private static function marca(string $marca): ?string
    {
        $marca = strtoupper(trim($marca));
        if ($marca === '') {
            return null;
        }
        return mb_substr(self::MARCAS[$marca] ?? self::titulo($marca), 0, 80);
    }

    private static function categoria(string $nombre, array &$cache): int
    {
        return $cache[$nombre] ??= Categoria::idPorNombre($nombre, null)
            ?? Categoria::create(['parent_id' => null, 'nombre' => $nombre, 'activo' => true]);
    }

    /** "PASO DE LOS TOROS" → "Paso de los Toros" (y algunas palabras con tilde). */
    private static function titulo(string $s): string
    {
        $chicas = ['de', 'del', 'la', 'las', 'los', 'y', 'con', 'sin'];
        $palabras = preg_split('/\s+/', mb_strtolower(trim($s))) ?: [];
        foreach ($palabras as $i => $p) {
            $mayus = strtoupper($p);
            if (isset(self::PALABRAS[$mayus])) {
                $palabras[$i] = self::PALABRAS[$mayus];
            } elseif ($i === 0 || !in_array($p, $chicas, true)) {
                $palabras[$i] = mb_strtoupper(mb_substr($p, 0, 1)) . mb_substr($p, 1);
            }
        }
        $texto = implode(' ', $palabras);
        return mb_strtoupper(mb_substr($texto, 0, 1)) . mb_substr($texto, 1);
    }

    private static function sinTildes(string $s): string
    {
        return strtr(mb_strtolower($s), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    }

    private static function precio(mixed $v): ?float
    {
        return is_numeric($v) && (float) $v > 0 ? round((float) $v, 2) : null;
    }

    private static function fecha(mixed $v): ?string
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }
}
