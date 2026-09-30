<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Categoria;
use App\Models\Producto;
use App\Requests\ProductoRequest;
use RuntimeException;
use ZipArchive;

/**
 * Carga masiva de productos desde un Excel (.xlsx) o .csv.
 * Si el producto ya existe (mismo código, o mismo nombre + marca + presentación) se actualiza;
 * si no, se crea. Las celdas vacías no borran lo que ya estaba cargado.
 */
final class ProductoImportService
{
    public const MAX_FILAS = 5000;
    public const MAX_BYTES = 5 * 1024 * 1024;

    /** Encabezados aceptados (sin tildes, en minúscula) => campo. */
    private const ENCABEZADOS = [
        'nombre' => 'nombre', 'producto' => 'nombre', 'articulo' => 'nombre', 'descripcion' => 'nombre', 'detalle' => 'nombre',
        'marca' => 'marca',
        'presentacion' => 'presentacion', 'contenido' => 'presentacion', 'tamano' => 'presentacion', 'medida' => 'presentacion', 'volumen' => 'presentacion',
        'codigo' => 'codigo_barras', 'codigo de barras' => 'codigo_barras', 'cod barras' => 'codigo_barras', 'codigo barras' => 'codigo_barras',
        'ean' => 'codigo_barras', 'barras' => 'codigo_barras', 'cod' => 'codigo_barras',
        'categoria' => 'categoria', 'rubro' => 'categoria', 'familia' => 'categoria',
        'subcategoria' => 'subcategoria', 'subrubro' => 'subcategoria', 'tipo' => 'subcategoria',
        'unidad' => 'unidad_medida', 'unidad de medida' => 'unidad_medida',
    ];

    /** Sin encabezados reconocibles se asume este orden de columnas. */
    private const ORDEN_POR_DEFECTO = ['nombre', 'marca', 'presentacion', 'codigo_barras', 'categoria', 'subcategoria'];

    /**
     * @return array<int, array<int, string>> filas (cada una, lista de celdas como texto)
     * @throws RuntimeException con un mensaje para mostrar
     */
    public static function leer(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('No se pudo subir el archivo. Probá de nuevo.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('El archivo es muy pesado (máximo 5 MB).');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        return match ($ext) {
            'xlsx'       => self::leerXlsx($file['tmp_name']),
            'csv', 'txt' => self::leerCsv($file['tmp_name']),
            'xls'        => throw new RuntimeException('Ese es el formato viejo de Excel (.xls). Abrilo y usá "Guardar como" → Libro de Excel (.xlsx).'),
            default      => throw new RuntimeException('Subí un archivo de Excel (.xlsx) o .csv.'),
        };
    }

    /**
     * @return array{creados: int, actualizados: int, sin_cambios: int, errores: array<int, string>}
     */
    public static function importar(array $filas): array
    {
        // Se conserva el número de fila original para los mensajes de error.
        $filas = array_filter($filas, fn ($f) => implode('', $f) !== '');
        if ($filas === []) {
            throw new RuntimeException('El archivo está vacío.');
        }

        $primera = array_key_first($filas);
        [$columnas, $conEncabezado] = self::columnas($filas[$primera]);
        if ($conEncabezado) {
            unset($filas[$primera]);
        }
        if (!in_array('nombre', $columnas, true)) {
            throw new RuntimeException('No encontré la columna con el nombre del producto. Poné en la primera fila: Nombre, Marca, Presentación, Código, Categoría.');
        }
        if (count($filas) > self::MAX_FILAS) {
            throw new RuntimeException('Son demasiadas filas (máximo ' . self::MAX_FILAS . '). Dividí el archivo en partes.');
        }

        $resultado = ['creados' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => []];
        $categorias = [];

        Database::transaction(function () use ($filas, $columnas, &$resultado, &$categorias): void {
            foreach ($filas as $i => $celdas) {
                $fila = [];
                foreach ($columnas as $pos => $campo) {
                    if ($campo !== null) {
                        $fila[$campo] = self::limpiar($celdas[$pos] ?? '');
                    }
                }
                $numero = $i + 1; // como lo ve en Excel

                if (($fila['nombre'] ?? '') === '') {
                    $resultado['errores'][$numero] = 'Falta el nombre.';
                    continue;
                }

                try {
                    $resultado[self::guardarFila($fila, $categorias)]++;
                } catch (RuntimeException $e) {
                    $resultado['errores'][$numero] = $e->getMessage();
                }
            }
        });

        return $resultado;
    }

    /** Plantilla .csv que Excel abre directo (separador ; y UTF-8 con BOM). */
    public static function plantilla(): string
    {
        $filas = [
            ['Nombre', 'Marca', 'Presentación', 'Código', 'Categoría', 'Subcategoría'],
            ['Fernet Branca', 'Branca', '750 ml', '', 'Bebidas con alcohol', 'Aperitivos'],
            ['Cerveza Quilmes lata', 'Quilmes', '473 cc', '', 'Bebidas con alcohol', 'Cervezas'],
            ['Coca-Cola', 'Coca-Cola', '2,25 L', '', 'Bebidas sin alcohol', 'Gaseosas'],
        ];
        return "\xEF\xBB\xBF" . implode("\r\n", array_map(fn ($f) => implode(';', $f), $filas)) . "\r\n";
    }

    // ───────────────────────────────────────────────────────────────

    /** @return 'creados'|'actualizados'|'sin_cambios' */
    private static function guardarFila(array $fila, array &$categorias): string
    {
        $codigo = (string) preg_replace('/\s+/', '', $fila['codigo_barras'] ?? '');
        $existente = $codigo !== '' ? Producto::findByCodigo($codigo, false) : null;
        $existente ??= Producto::duplicado($fila['nombre'], self::nulo($fila['marca'] ?? ''), self::nulo($fila['presentacion'] ?? ''));
        if ($existente !== null) {
            $existente = Producto::find((int) $existente['id']);
        }

        $presentacion = $fila['presentacion'] ?? '';
        $datos = [
            'nombre'        => $fila['nombre'],
            'marca'         => $fila['marca'] ?? '',
            'presentacion'  => $presentacion,
            'codigo_barras' => $codigo,
            'categoria_id'  => self::categoriaId($fila['categoria'] ?? '', $fila['subcategoria'] ?? '', $categorias),
            'unidad_medida' => self::unidad($fila['unidad_medida'] ?? '', $presentacion),
            'descripcion'   => '',
        ];

        // Vacío en el Excel = dejar lo que ya estaba.
        if ($existente !== null) {
            foreach ($datos as $campo => $valor) {
                if ($valor === '' || $valor === null) {
                    $datos[$campo] = $existente[$campo] ?? '';
                }
            }
        }
        $datos = array_map(fn ($v) => $v === null ? '' : (string) $v, $datos);
        $datos['activo'] = $existente === null || $existente['activo'] ? '1' : '0';

        $id = $existente !== null ? (int) $existente['id'] : null;
        [$data, $errors] = ProductoRequest::validate($datos, $id);
        if ($errors) {
            throw new RuntimeException(implode(' ', array_unique($errors)));
        }

        if ($id === null) {
            Producto::create($data);
            return 'creados';
        }

        foreach ($data as $campo => $valor) {
            if ((string) ($existente[$campo] ?? '') !== (string) ($valor ?? '')) {
                Producto::update($id, $data);
                return 'actualizados';
            }
        }
        return 'sin_cambios';
    }

    /** Busca la categoría (y subcategoría) por nombre; si no existe, la crea. */
    private static function categoriaId(string $categoria, string $sub, array &$cache): ?int
    {
        // "Bebidas con alcohol > Cervezas" en una sola celda también vale.
        if ($sub === '' && preg_match('/^(.+?)\s*[>›\/]\s*(.+)$/u', $categoria, $m)) {
            [$categoria, $sub] = [$m[1], $m[2]];
        }
        if ($categoria === '' && $sub === '') {
            return null;
        }

        $padre = $categoria !== '' ? self::categoria($categoria, null, $cache) : null;
        return $sub !== '' ? self::categoria($sub, $padre, $cache) : $padre;
    }

    private static function categoria(string $nombre, ?int $padre, array &$cache): int
    {
        $nombre = mb_substr($nombre, 0, 80);
        $clave = ($padre ?? 0) . '|' . mb_strtolower($nombre);
        if (!isset($cache[$clave])) {
            $cache[$clave] = Categoria::idPorNombre($nombre, $padre)
                ?? Categoria::create(['parent_id' => $padre, 'nombre' => $nombre, 'activo' => true]);
        }
        return $cache[$clave];
    }

    /** Unidad escrita en el Excel, o deducida de la presentación ("750 ml", "2,25 L", "473 cc"). */
    private static function unidad(string $unidad, string $presentacion): string
    {
        $texto = self::normalizar($unidad !== '' ? $unidad : $presentacion);
        return match (true) {
            (bool) preg_match('/(^|[\d\s])(ml|mililitros?)$/', $texto)      => 'ml',
            (bool) preg_match('/(^|[\d\s])(cc|cm3|centimetros?)$/', $texto) => 'cc',
            (bool) preg_match('/(^|[\d\s])(l|lt|lts|litros?)$/', $texto)    => 'l',
            (bool) preg_match('/(^|[\d\s])(u|un|unidad(es)?)$/', $texto)    => 'u',
            default                                                         => '',
        };
    }

    /** @return array{0: array<int, ?string>, 1: bool} campo por posición, y si la primera fila es de encabezados */
    private static function columnas(array $primera): array
    {
        $columnas = [];
        foreach ($primera as $pos => $celda) {
            $campo = self::ENCABEZADOS[self::normalizar($celda)] ?? null;
            // Una misma columna no se asigna dos veces (ej: "Producto" y "Descripción").
            $columnas[$pos] = in_array($campo, $columnas, true) ? null : $campo;
        }

        return array_filter($columnas) !== [] ? [$columnas, true] : [self::ORDEN_POR_DEFECTO, false];
    }

    private static function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', '.' => '', ':' => '', '_' => ' ']);
        return trim((string) preg_replace('/\s+/', ' ', $s));
    }

    private static function limpiar(string $s): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $s));
    }

    private static function nulo(string $s): ?string
    {
        return $s === '' ? null : $s;
    }

    // ── Lectores ───────────────────────────────────────────────────

    private static function leerCsv(string $ruta): array
    {
        $texto = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($ruta));
        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');
        }

        // Separador: el que más aparece en la primera línea (Excel en castellano usa ;).
        $primera = strtok($texto, "\n") ?: '';
        $cuenta = [';' => substr_count($primera, ';'), ',' => substr_count($primera, ','), "\t" => substr_count($primera, "\t")];
        arsort($cuenta);
        $separador = (string) array_key_first($cuenta);

        $h = fopen('php://temp', 'r+');
        fwrite($h, $texto);
        rewind($h);
        $filas = [];
        while (($f = fgetcsv($h, 0, $separador, '"', '')) !== false && count($filas) <= self::MAX_FILAS + 1) {
            $filas[] = array_map(fn ($c) => (string) $c, $f);
        }
        fclose($h);
        return $filas;
    }

    /** Lee la primera hoja de un .xlsx (es un zip con XML adentro). */
    private static function leerXlsx(string $ruta): array
    {
        $archivo = self::abrirZip($ruta);

        $compartidos = [];
        if (($xml = $archivo('xl/sharedStrings.xml')) !== null) {
            foreach (self::xml($xml)->si as $si) {
                // Texto simple (<t>) o con formato (varios <r><t>).
                $compartidos[] = self::textos($si);
            }
        }

        $hoja = $archivo(self::primeraHoja($archivo));
        if ($hoja === null) {
            throw new RuntimeException('El Excel no tiene hojas con datos.');
        }

        $filas = [];
        foreach (self::xml($hoja)->sheetData->row as $row) {
            $numero = (int) $row['r'] ?: count($filas) + 1;
            $fila = [];
            foreach ($row->c as $c) {
                $fila[self::columnaIndice((string) $c['r']) ?? count($fila)] = self::valorCelda($c, $compartidos);
            }
            if ($fila !== []) {
                // Índice = número de fila en Excel - 1, así los errores dicen la fila correcta.
                $filas[$numero - 1] = array_replace(array_fill(0, max(array_keys($fila)) + 1, ''), $fila);
            }
            if (count($filas) > self::MAX_FILAS + 1) {
                break;
            }
        }
        return $filas;
    }

    /**
     * Devuelve una función nombre => contenido (o null). Usa ZipArchive si está;
     * si no, lee el zip a mano (solo hace falta zlib, que viene siempre).
     */
    private static function abrirZip(string $ruta): \Closure
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($ruta) !== true) {
                throw new RuntimeException('No pude abrir el Excel. ¿Está dañado? Probá guardarlo de nuevo.');
            }
            $contenidos = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nombre = (string) $zip->getNameIndex($i);
                if (str_ends_with($nombre, '.xml') || str_ends_with($nombre, '.rels')) {
                    $contenidos[$nombre] = (string) $zip->getFromIndex($i);
                }
            }
            $zip->close();
            return fn (string $n): ?string => $contenidos[$n] ?? null;
        }

        $datos = (string) file_get_contents($ruta);
        // Fin del directorio central: firma PK\5\6 cerca del final.
        $fin = strrpos($datos, "PK\x05\x06");
        if ($fin === false) {
            throw new RuntimeException('No pude abrir el Excel. ¿Está dañado? Probá guardarlo de nuevo.');
        }
        $eocd = unpack('vdisco/vdiscoDir/ventradas/vtotal/Vtamano/Vinicio', substr($datos, $fin + 4, 16));
        $pos = $eocd['inicio'];
        $entradas = [];
        for ($i = 0; $i < $eocd['total']; $i++) {
            if (substr($datos, $pos, 4) !== "PK\x01\x02") {
                break;
            }
            $h = unpack('vversion/vnecesita/vflags/vmetodo/vhora/vfecha/Vcrc/Vcomprimido/Vtamano/vlargoNombre/vlargoExtra/vlargoComentario/vdisco/vinternos/Vexternos/Vlocal', substr($datos, $pos + 4, 42));
            $nombre = substr($datos, $pos + 46, $h['largoNombre']);
            $entradas[$nombre] = $h;
            $pos += 46 + $h['largoNombre'] + $h['largoExtra'] + $h['largoComentario'];
        }

        return function (string $n) use ($entradas, $datos): ?string {
            $h = $entradas[$n] ?? null;
            if ($h === null) {
                return null;
            }
            $local = unpack('vlargoNombre/vlargoExtra', substr($datos, $h['local'] + 26, 4));
            $crudo = substr($datos, $h['local'] + 30 + $local['largoNombre'] + $local['largoExtra'], $h['comprimido']);
            $contenido = match ($h['metodo']) {
                0       => $crudo,
                8       => @gzinflate($crudo, 50 * 1024 * 1024),
                default => false,
            };
            if ($contenido === false) {
                throw new RuntimeException('No pude leer el Excel. Probá guardarlo como .csv.');
            }
            return $contenido;
        };
    }

    private static function primeraHoja(\Closure $archivo): string
    {
        $libro = $archivo('xl/workbook.xml');
        $rels = $archivo('xl/_rels/workbook.xml.rels');
        if ($libro !== null && $rels !== null) {
            $hoja = self::xml($libro)->sheets->sheet[0] ?? null;
            if ($hoja !== null) {
                $rid = (string) $hoja->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                foreach (self::xml($rels)->Relationship as $r) {
                    if ((string) $r['Id'] === $rid) {
                        $destino = ltrim((string) $r['Target'], '/');
                        return str_starts_with($destino, 'xl/') ? $destino : 'xl/' . $destino;
                    }
                }
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    private static function valorCelda(\SimpleXMLElement $c, array $compartidos): string
    {
        $v = (string) ($c->v ?? '');
        return match ((string) $c['t']) {
            'inlineStr' => self::textos($c),
            's'         => $compartidos[(int) $v] ?? '',
            'b'         => $v === '1' ? 'SI' : 'NO',
            'e'         => '',
            'str'       => $v,
            default     => self::numero($v),
        };
    }

    /** Une todos los <t> de un nodo (texto con formato viene partido en varios). */
    private static function textos(\SimpleXMLElement $nodo): string
    {
        return implode('', array_map('strval', $nodo->xpath('.//*[local-name()="t"]') ?: []));
    }

    /** Los códigos de barras vienen como número: 7.79E+12 → 7790000000011. */
    private static function numero(string $v): string
    {
        if ($v === '' || !is_numeric($v)) {
            return $v;
        }
        $f = (float) $v;
        if (stripos($v, 'e') !== false || (str_contains($v, '.') && $f === floor($f))) {
            return sprintf('%.0f', $f);
        }
        return $v;
    }

    private static function columnaIndice(string $ref): ?int
    {
        if (!preg_match('/^([A-Z]+)/', $ref, $m)) {
            return null;
        }
        $n = 0;
        foreach (str_split($m[1]) as $letra) {
            $n = $n * 26 + (ord($letra) - 64);
        }
        return $n - 1;
    }

    private static function xml(string $contenido): \SimpleXMLElement
    {
        $xml = simplexml_load_string($contenido, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        if ($xml === false) {
            throw new RuntimeException('No pude leer el Excel. Probá guardarlo como .csv.');
        }
        return $xml;
    }
}
