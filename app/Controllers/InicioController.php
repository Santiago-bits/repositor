<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Configuracion;
use App\Models\Fecha;
use App\Models\Local;
use App\Models\Producto;
use App\Services\DeteccionLocalService;

/** El anotador: el GPS elige el local (o lo tocás), buscás o escaneás el producto y le ponés la fecha. */
final class InicioController extends Controller
{
    public function index(): void
    {
        $user = auth();
        $locales = Local::paraUsuario($user);
        $elegido = (int) Request::input('local', 0);
        if ($elegido > 0) {
            Session::set('local_id', $elegido);
        }
        $localId = $elegido ?: (int) Session::get('local_id', 0);
        $local = null;
        foreach ($locales as $l) {
            if ((int) $l['id'] === $localId) {
                $local = $l;
            }
        }

        $this->view('app/inicio', [
            'title'        => 'Anotar',
            'locales'      => $locales,
            'local'        => $local,
            'cuenta'       => Fecha::contarPorLocal((int) $user['id'], self::diasUrgente()),
            'fechas'       => $local ? Fecha::pendientes((int) $user['id'], (int) $local['id']) : [],
            // Al abrir la app (sin haber tocado un local) el GPS busca en qué local estás.
            'autoDetectar' => $elegido === 0,
            'scripts'      => ['assets/js/escaner.js', 'assets/js/anotador.js'],
        ]);
    }

    /** Todas mis fechas pendientes, por local (el admin ve las de todo el equipo). */
    public function fechas(): void
    {
        $todas = Fecha::pendientes(is_admin() ? null : Auth::id());
        $porLocal = [];
        foreach ($todas as $f) {
            $porLocal[(int) $f['local_id']] ??= ['nombre' => $f['local'], 'fechas' => []];
            $porLocal[(int) $f['local_id']]['fechas'][] = $f;
        }
        $this->view('app/fechas', [
            'title'    => 'Fechas',
            'porLocal' => $porLocal,
            'total'    => count($todas),
            'scripts'  => ['assets/js/anotador.js'],
        ]);
    }

    /** Anotar una fecha (JSON). Devuelve la lista del local actualizada. */
    public function guardar(): never
    {
        $user = auth();
        $local = Local::accesible($user, (int) Request::input('local_id', 0));
        if ($local === null) {
            $this->json(['ok' => false, 'message' => 'Elegí el local.'], 422);
        }

        $productoId = (int) Request::input('producto_id', 0) ?: null;
        if ($productoId !== null && Producto::find($productoId) === null) {
            $productoId = null;
        }
        $texto = trim((string) Request::input('producto_texto', ''));
        if ($productoId === null && ($texto === '' || mb_strlen($texto) > 150 || !mb_check_encoding($texto, 'UTF-8'))) {
            $this->json(['ok' => false, 'message' => 'Escribí o escaneá el producto.'], 422);
        }

        $fecha = self::leerFecha((string) Request::input('fecha', ''));
        if ($fecha === null) {
            $this->json(['ok' => false, 'message' => 'La fecha no es válida. Escribila así: 15/11 o 15/11/26.'], 422);
        }

        $cantidad = filter_var(Request::input('cantidad', ''), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999]]);
        $nota = trim((string) Request::input('nota', ''));
        $nota = $nota === '' || !mb_check_encoding($nota, 'UTF-8') ? null : mb_substr($nota, 0, 120);

        $id = Fecha::crear([
            'user_id'           => (int) $user['id'],
            'local_id'          => (int) $local['id'],
            'producto_id'       => $productoId,
            'producto_texto'    => $productoId === null ? $texto : null,
            'fecha_vencimiento' => $fecha,
            'cantidad'          => $cantidad === false ? null : $cantidad,
            'nota'              => $nota,
        ]);

        $fechas = Fecha::pendientes((int) $user['id'], (int) $local['id']);
        $this->json([
            'ok'      => true,
            'id'      => $id,
            'mensaje' => 'Anotado: ' . Fecha::nombre(Fecha::find($id)) . ' · ' . fecha($fecha, 'd/m/Y'),
            'lista'   => partial('fechas-lista', ['fechas' => $fechas, 'conLocal' => false, 'nueva' => $id]),
            'total'   => count($fechas),
        ]);
    }

    /** "Ya no está": vendido o retirado (JSON). */
    public function retirar(int $id): never
    {
        $this->propia($id);
        Fecha::retirar($id);
        $this->json(['ok' => true]);
    }

    public function eliminar(int $id): never
    {
        $this->propia($id);
        Fecha::eliminar($id);
        $this->json(['ok' => true]);
    }

    /** Productos del catálogo mientras se escribe (JSON). */
    public function productos(): never
    {
        $q = trim((string) Request::input('q', ''));
        $this->json(['productos' => array_map(fn ($p) => [
            'id'     => (int) $p['id'],
            'nombre' => Producto::nombreCompleto($p),
            'marca'  => (string) $p['marca'],
        ], mb_strlen($q) < 2 ? [] : Producto::buscar($q, true, 8))]);
    }

    /** Recibe la ubicación del celular y devuelve el/los locales cercanos (JSON). No guarda nada. */
    public function detectar(): never
    {
        $lat = Request::input('lat');
        $lng = Request::input('lng');
        $precision = Request::input('precision', 0);

        if (!is_numeric($lat) || !is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180 || !is_numeric($precision)) {
            $this->json(['message' => 'No se pudo leer tu ubicación. Probá de nuevo.'], 422);
        }
        $this->json(DeteccionLocalService::detectar(auth(), (float) $lat, (float) $lng, (float) $precision));
    }

    /** Acepta 15/11, 15/11/26, 15/11/2026 y 2026-11-15. "15/11" sin año: este año, o el que viene si ya pasó hace más de un mes. */
    public static function leerFecha(string $texto): ?string
    {
        $t = trim($texto);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $t, $m)) {
            [$anio, $mes, $dia] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('#^(\d{1,2})[/.\-](\d{1,2})(?:[/.\-](\d{2}|\d{4}))?$#', $t, $m)) {
            [$dia, $mes] = [(int) $m[1], (int) $m[2]];
            if (isset($m[3]) && $m[3] !== '') {
                $anio = (int) $m[3] + ((int) $m[3] < 100 ? 2000 : 0);
            } else {
                $anio = (int) date('Y');
                if (checkdate($mes, $dia, $anio) && mktime(0, 0, 0, $mes, $dia, $anio) < strtotime('-31 days')) {
                    $anio++;
                }
            }
        } else {
            return null;
        }
        if (!checkdate($mes, $dia, $anio)) {
            return null;
        }
        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
        return $fecha < date('Y-m-d', strtotime('-1 year')) || $fecha > date('Y-m-d', strtotime('+15 years')) ? null : $fecha;
    }

    private function propia(int $id): array
    {
        $fecha = Fecha::find($id);
        if ($fecha === null || ((int) $fecha['user_id'] !== Auth::id() && !is_admin())) {
            $this->json(['ok' => false, 'message' => 'Esa fecha no existe.'], 404);
        }
        return $fecha;
    }

    private static function diasUrgente(): int
    {
        return (int) Configuracion::get('vencimiento_dias_critico', '3');
    }
}
