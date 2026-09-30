<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\Relevamiento;
use App\Services\VisitaService;

/** Permisos y respuestas comunes a todo lo que se hace dentro de una visita. */
abstract class VisitaBaseController extends Controller
{
    /** El repositor ve sus visitas; el admin, todas. */
    protected function visitaVisible(int $id): array
    {
        $visita = $this->notFoundUnless(Relevamiento::find($id));
        if ((int) $visita['user_id'] !== Auth::id() && !is_admin()) {
            View::error(403);
        }
        return $visita;
    }

    /**
     * Solo quien hace la visita puede cargar datos. Las de hoy siempre se pueden tocar:
     * si se había cerrado sola (por irse del local), se vuelve a abrir sin preguntar.
     */
    protected function visitaEditable(int $id): array
    {
        $visita = $this->notFoundUnless(Relevamiento::find($id));
        if ((int) $visita['user_id'] !== Auth::id()) {
            View::error(403);
        }
        if ($visita['estado'] === 'en_proceso') {
            return $visita;
        }
        if ($visita['estado'] === 'finalizado' && $visita['fecha'] === date('Y-m-d')) {
            return VisitaService::reabrir($visita);
        }
        $this->fallar('Esta visita es de otro día: ya no se pueden cargar datos.', 409, '/visitas/' . $id);
    }

    /** Éxito: JSON para las acciones hechas con fetch, redirección para formularios comunes. */
    protected function responder(string $mensaje, array $extra = [], ?string $volver = null): never
    {
        if (Request::wantsJson()) {
            $this->json(['ok' => true, 'message' => $mensaje] + $extra);
        }
        flash('success', $mensaje);
        $volver !== null ? redirect($volver) : back();
    }

    protected function fallar(string $mensaje, int $status = 422, ?string $volver = null): never
    {
        if (Request::wantsJson()) {
            $this->json(['ok' => false, 'message' => $mensaje], $status);
        }
        flash('error', $mensaje);
        $volver !== null ? redirect($volver) : back();
    }
}
