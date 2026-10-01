<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\Producto;
use App\Models\ProductoLocal;
use App\Models\Vencimiento;
use App\Services\RegistroVisitaService;

/** Vencimientos de la visita en una sola pantalla: buscás el producto, le ponés fecha, cantidad y nota. */
final class VisitaVencimientoController extends VisitaBaseController
{
    public function crear(int $id): void
    {
        $visita = $this->visitaEditable($id);

        $this->view('app/visitas/vencimientos', [
            'title'     => 'Vencimientos',
            'visita'    => $visita,
            'productos' => ProductoLocal::productosDeLocal((int) $visita['local_id'], $id),
            'cargados'  => Vencimiento::deVisita($id),
            'scripts'   => ['assets/js/visita.js'],
        ]);
    }

    /** Entrada: venc[n][producto|fecha|cantidad|nota]. Las filas sin fecha válida no se guardan. */
    public function guardar(int $id): void
    {
        $visita = $this->visitaEditable($id);
        $hasta = date('Y-m-d', strtotime('+15 years'));

        $filas = [];
        $salteadas = 0;
        foreach ((array) ($_POST['venc'] ?? []) as $f) {
            if (!is_array($f)) {
                continue;
            }
            $productoId = (int) ($f['producto'] ?? 0);
            $fecha = (string) ($f['fecha'] ?? '');
            $d = \DateTime::createFromFormat('!Y-m-d', $fecha);
            $producto = $productoId > 0 ? Producto::find($productoId) : null;

            if ($producto === null || !$d || $d->format('Y-m-d') !== $fecha || $fecha < '2000-01-01' || $fecha > $hasta) {
                $salteadas++;
                continue;
            }

            $cantidad = filter_var($f['cantidad'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999]]);
            $nota = trim((string) ($f['nota'] ?? ''));
            $nota = $nota === '' || !mb_check_encoding($nota, 'UTF-8') ? null : mb_substr($nota, 0, 120);

            $filas[] = [$productoId, $fecha, $cantidad === false ? null : $cantidad, $nota];
        }

        if ($filas === []) {
            flash('error', $salteadas ? 'Poné la fecha de vencimiento en cada producto.' : 'Elegí al menos un producto.');
            redirect("/visitas/{$id}/vencimientos");
        }

        Database::transaction(function () use ($visita, $filas): void {
            foreach ($filas as [$productoId, $fecha, $cantidad, $nota]) {
                RegistroVisitaService::agregarVencimiento($visita, $productoId, $fecha, $cantidad, $nota);
            }
        });

        $n = count($filas);
        flash('success', ($n === 1 ? 'Se guardó 1 vencimiento.' : "Se guardaron {$n} vencimientos.")
            . ($salteadas ? " {$salteadas} sin fecha no se guardaron." : ''));
        redirect("/visitas/{$id}/vencimientos");
    }
}
