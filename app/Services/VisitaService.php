<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Local;
use App\Models\Relevamiento;
use RuntimeException;

final class VisitaService
{
    /**
     * Inicia una visita (o continúa la que ya está abierta en ese mismo local hoy).
     *
     * @throws RuntimeException con un mensaje para mostrar al usuario
     */
    public static function iniciar(array $user, int $localId, ?float $lat, ?float $lng, bool $cerrarAnterior): int
    {
        $local = Local::accesible($user, $localId);
        if ($local === null) {
            throw new RuntimeException('Ese local no está disponible para vos.');
        }

        return Database::transaction(function () use ($user, $localId, $lat, $lng, $cerrarAnterior, $local): int {
            $abierta = Relevamiento::abiertaDeUsuario((int) $user['id']);

            if ($abierta !== null) {
                if ((int) $abierta['local_id'] === $localId && $abierta['fecha'] === date('Y-m-d')) {
                    return (int) $abierta['id'];
                }
                if (!$cerrarAnterior) {
                    throw new RuntimeException("Tenés una visita abierta en {$abierta['local']}. Finalizala antes de ingresar a {$local['nombre']}.");
                }
                self::finalizar($abierta);
            }

            return Relevamiento::create((int) $user['id'], $localId, $lat, $lng);
        });
    }

    /**
     * Si la visita es de un día anterior (quedó abierta), se cierra con la hora de su
     * última actividad y no con la de ahora, así la duración no queda inflada.
     */
    public static function finalizar(array $visita): void
    {
        $fin = $visita['fecha'] === date('Y-m-d')
            ? date('Y-m-d H:i:s')
            : (Relevamiento::ultimaActividad((int) $visita['id']) ?? $visita['inicio_at']);

        Relevamiento::cerrar((int) $visita['id'], 'finalizado', $fin);
    }

    public static function cancelar(array $visita): void
    {
        Relevamiento::cerrar((int) $visita['id'], 'cancelado', date('Y-m-d H:i:s'));
    }
}
