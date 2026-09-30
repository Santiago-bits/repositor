<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Local;
use App\Models\Producto;
use App\Models\Promocion;
use App\Requests\PromocionRequest;
use App\Services\PromocionService;

final class PromocionController extends Controller
{
    private const FILTROS = ['vigentes' => 'Vigentes', 'proximas' => 'Próximas', 'terminadas' => 'Terminadas', 'todas' => 'Todas'];

    public function index(): void
    {
        $filtro = (string) Request::input('filtro', 'vigentes');
        $filtro = isset(self::FILTROS[$filtro]) ? $filtro : 'vigentes';
        $localId = (int) Request::input('local_id', 0) ?: null;

        $this->view('admin/promociones/index', [
            'title'       => 'Promociones',
            'filtros'     => self::FILTROS,
            'filtro'      => $filtro,
            'localId'     => $localId,
            'locales'     => Local::all(),
            'promociones' => Promocion::listar($filtro, $localId),
        ]);
    }

    public function create(): void
    {
        $this->form('Nueva promoción', null, []);
    }

    public function store(): void
    {
        $data = $this->validar(null, '/admin/promociones/crear');
        PromocionService::guardar($data, $data['locales'], (int) auth()['id']);
        flash('success', 'Promoción creada.');
        redirect('/admin/promociones');
    }

    public function edit(int $id): void
    {
        $promo = $this->notFoundUnless(Promocion::find($id));
        $this->form('Editar promoción', $promo, Promocion::localIds($id));
    }

    public function update(int $id): void
    {
        $this->notFoundUnless(Promocion::find($id));
        $data = $this->validar($id, "/admin/promociones/{$id}/editar");
        PromocionService::guardar($data, $data['locales'], (int) auth()['id'], $id);
        flash('success', 'Promoción actualizada.');
        redirect('/admin/promociones');
    }

    public function cancelar(int $id): void
    {
        $promo = $this->notFoundUnless(Promocion::find($id));
        Promocion::setEstado($id, 'cancelada');
        flash('success', 'Promoción de ' . Producto::nombreCompleto($promo) . ' cancelada.');
        redirect('/admin/promociones');
    }

    private function validar(?int $id, string $volver): array
    {
        [$data, $errors] = PromocionRequest::validate($_POST);
        if ($data['locales'] === []) {
            $errors['locales'] = 'Elegí al menos un local.';
        }
        if (!$errors && $data['estado'] === 'activa') {
            $solapada = Promocion::solapada($data['producto_id'], $data['locales'], $data['fecha_inicio'], $data['fecha_fin'], $id);
            if ($solapada) {
                $errors['producto_id'] = "Ya hay una promo activa de ese producto en {$solapada['local']} para esas fechas.";
            }
        }
        if ($errors) {
            $old = $_POST;
            $old['locales'] ??= [];
            $this->backWithErrors($errors, $old, $volver);
        }
        return $data;
    }

    private function form(string $title, ?array $promo, array $asignados): void
    {
        $productos = [];
        foreach (Producto::todos() as $p) {
            if ($p['activo'] || (int) $p['id'] === (int) ($promo['producto_id'] ?? 0)) {
                $productos[(int) $p['id']] = Producto::nombreCompleto($p) . ($p['marca'] ? " · {$p['marca']}" : '');
            }
        }

        $this->view('admin/promociones/form', [
            'title'     => $title,
            'promo'     => $promo ?? ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => PromocionService::finPorDefecto()],
            'esNueva'   => $promo === null,
            'grupos'    => ['Productos' => $productos],
            'atajos'    => PromocionService::atajosFin(),
            'locales'   => Local::all(),
            'asignados' => $asignados,
            'scripts'   => ['assets/js/escaner.js'],
        ]);
    }
}
