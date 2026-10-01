<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Local;
use App\Requests\LocalRequest;
use App\Services\DeteccionLocalService;
use App\Services\UbicacionService;

final class LocalController extends Controller
{
    public function index(): void
    {
        $buscar = trim((string) Request::input('q', ''));

        $this->view('admin/locales/index', [
            'title'   => 'Locales',
            'buscar'  => $buscar,
            'locales' => self::marcarUbicacionesRepetidas(Local::all($buscar)),
        ]);
    }

    /**
     * Locales a menos de 30 m de otro: casi seguro se cargaron desde el mismo lugar
     * (por ejemplo con "Usar mi ubicación actual" sin estar en la puerta) y la detección falla.
     */
    private static function marcarUbicacionesRepetidas(array $locales): array
    {
        foreach ($locales as $i => $a) {
            $locales[$i]['cerca_de'] = [];
            if ($a['latitud'] === null) {
                continue;
            }
            foreach ($locales as $j => $b) {
                if ($i !== $j && $b['latitud'] !== null && DeteccionLocalService::distancia(
                    (float) $a['latitud'], (float) $a['longitud'], (float) $b['latitud'], (float) $b['longitud']
                ) < 30) {
                    $locales[$i]['cerca_de'][] = $b['nombre'];
                }
            }
        }
        return $locales;
    }

    public function create(): void
    {
        $this->view('admin/locales/form', ['title' => 'Nuevo local', 'local' => null]);
    }

    public function store(): void
    {
        [$data, $errors] = LocalRequest::validate($post = self::completarUbicacion($_POST));
        if ($errors) {
            $this->backWithErrors($errors, $post, '/admin/locales/crear');
        }

        Local::create($data);
        flash('success', "Local «{$data['nombre']}» creado.");
        redirect('/admin/locales');
    }

    public function edit(int $id): void
    {
        $local = $this->notFoundUnless(Local::find($id));
        $this->view('admin/locales/form', ['title' => 'Editar local', 'local' => $local]);
    }

    public function update(int $id): void
    {
        $this->notFoundUnless(Local::find($id));

        [$data, $errors] = LocalRequest::validate($post = self::completarUbicacion($_POST));
        if ($errors) {
            $this->backWithErrors($errors, $post, "/admin/locales/{$id}/editar");
        }

        Local::update($id, $data);
        flash('success', "Local «{$data['nombre']}» actualizado.");
        redirect('/admin/locales');
    }

    public function eliminar(int $id): void
    {
        $local = $this->notFoundUnless(Local::find($id));
        Local::eliminar($id);
        flash('success', "Local «{$local['nombre']}» borrado.");
        redirect('/admin/locales');
    }

    /** Link de Google Maps / Plus Code / coordenadas → {lat, lng} (para el botón "Buscar" del formulario). */
    public function ubicacion(): never
    {
        $coords = UbicacionService::resolver((string) Request::input('texto', ''));
        if ($coords === null) {
            $this->json(['ok' => false, 'message' => 'No encontré la ubicación en ese texto. Copiá el link desde "Compartir" en Google Maps.'], 422);
        }
        $this->json(['ok' => true] + $coords);
    }

    /**
     * Si no se cargó la ubicación a mano, se saca del link de Maps, de lo pegado en «Latitud»
     * o del Plus Code de la dirección. Un link pegado en la dirección no queda como dirección.
     */
    private static function completarUbicacion(array $post): array
    {
        $lat = trim((string) ($post['latitud'] ?? ''));
        $lng = trim((string) ($post['longitud'] ?? ''));
        $direccion = trim((string) ($post['direccion'] ?? ''));
        $esLink = (bool) preg_match('~^https?://~i', $direccion);

        if ($lng !== '' && is_numeric($lat) && !$esLink && trim((string) ($post['maps_link'] ?? '')) === '') {
            return $post;
        }

        foreach ([$post['maps_link'] ?? '', $lng === '' ? $lat : '', $direccion] as $texto) {
            $coords = UbicacionService::resolver((string) $texto);
            if ($coords !== null) {
                $post['latitud'] = (string) $coords['lat'];
                $post['longitud'] = (string) $coords['lng'];
                break;
            }
        }
        if ($esLink) {
            $post['direccion'] = '';
        }
        return $post;
    }

    public function toggle(int $id): void
    {
        $local = $this->notFoundUnless(Local::find($id));
        $activo = !$local['activo'];
        Local::setActivo($id, $activo);

        flash('success', "Local «{$local['nombre']}» " . ($activo ? 'activado.' : 'desactivado.'));
        redirect('/admin/locales');
    }
}
