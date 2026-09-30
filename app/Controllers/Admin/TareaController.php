<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Local;
use App\Models\Tarea;
use App\Requests\TareaRequest;

final class TareaController extends Controller
{
    public function index(): void
    {
        $this->view('admin/tareas/index', ['title' => 'Tareas', 'tareas' => Tarea::todas()]);
    }

    public function create(): void
    {
        $this->form('Nueva tarea', null, array_map(fn ($l) => (int) $l['id'], Local::all()));
    }

    public function store(): void
    {
        $data = $this->validar('/admin/tareas/crear');
        $this->guardar($data, null);
        flash('success', "Tarea «{$data['nombre']}» creada.");
        redirect('/admin/tareas');
    }

    public function edit(int $id): void
    {
        $tarea = $this->notFoundUnless(Tarea::find($id));
        $this->form('Editar tarea', $tarea, Tarea::localIds($id));
    }

    public function update(int $id): void
    {
        $this->notFoundUnless(Tarea::find($id));
        $data = $this->validar("/admin/tareas/{$id}/editar");
        $this->guardar($data, $id);
        flash('success', "Tarea «{$data['nombre']}» actualizada.");
        redirect('/admin/tareas');
    }

    public function eliminar(int $id): void
    {
        $tarea = $this->notFoundUnless(Tarea::find($id));
        Tarea::eliminar($id);
        flash('success', "Tarea «{$tarea['nombre']}» eliminada.");
        redirect('/admin/tareas');
    }

    private function validar(string $volver): array
    {
        [$data, $errors] = TareaRequest::validate($_POST);
        if ($errors) {
            $old = $_POST;
            $old['locales'] ??= [];
            $old['dias_semana'] ??= [];
            $this->backWithErrors($errors, $old, $volver);
        }
        return $data;
    }

    private function guardar(array $data, ?int $id): void
    {
        Database::transaction(function () use ($data, $id): void {
            if ($id === null) {
                $id = Tarea::create($data);
            } else {
                Tarea::update($id, $data);
            }
            Tarea::syncLocales($id, Local::existingIds($data['locales']));
        });
    }

    private function form(string $title, ?array $tarea, array $asignados): void
    {
        $this->view('admin/tareas/form', [
            'title'     => $title,
            'tarea'     => $tarea,
            'locales'   => Local::all(),
            'asignados' => $asignados,
        ]);
    }
}
