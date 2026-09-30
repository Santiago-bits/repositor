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
     * Entra a un local sin trámites: cierra la visita que haya quedado abierta en otro lado
     * y, si ya estuvo hoy en este local, sigue con esa misma visita en vez de crear otra.
     *
     * @throws RuntimeException con un mensaje para mostrar al usuario
     */
    public static function iniciar(array $user, int $localId, ?float $lat, ?float $lng): int
    {
        if (Local::accesible($user, $localId) === null) {
            throw new RuntimeException('Ese local no está disponible para vos.');
        }

        return Database::transaction(function () use ($user, $localId, $lat, $lng): int {
            $userId = (int) $user['id'];
            $abierta = Relevamiento::abiertaDeUsuario($userId);

            if ($abierta !== null) {
                if ((int) $abierta['local_id'] === $localId && $abierta['fecha'] === date('Y-m-d')) {
                    return (int) $abierta['id'];
                }
                self::finalizar($abierta);
            }

            $deHoy = Relevamiento::deHoyEnLocal($userId, $localId);
            if ($deHoy !== null) {
                Relevamiento::reabrir((int) $deHoy['id']);
                return (int) $deHoy['id'];
            }
            return Relevamiento::create($userId, $localId, $lat, $lng);
        });
    }

    /** La visita abierta del usuario; si quedó abierta de otro día, la cierra y devuelve null. */
    public static function abierta(int $userId): ?array
    {
        $abierta = Relevamiento::abiertaDeUsuario($userId);
        if ($abierta !== null && $abierta['fecha'] !== date('Y-m-d')) {
            self::finalizar($abierta);
            return null;
        }
        return $abierta;
    }

    /** Volver a cargar datos en una visita de hoy ya cerrada: se reabre (y se cierra cualquier otra). */
    public static function reabrir(array $visita): array
    {
        Database::transaction(function () use ($visita): void {
            $abierta = Relevamiento::abiertaDeUsuario((int) $visita['user_id']);
            if ($abierta !== null && (int) $abierta['id'] !== (int) $visita['id']) {
                self::finalizar($abierta);
            }
            Relevamiento::reabrir((int) $visita['id']);
        });
        return Relevamiento::find((int) $visita['id']);
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

    /** Borra la visita y todo lo cargado en ella, incluidos los archivos de las fotos. */
    public static function eliminar(array $visita): void
    {
        $fotos = Database::transaction(fn () => Relevamiento::eliminar((int) $visita['id']));
        foreach ($fotos as $path) {
            ImageService::eliminar($path);
        }
    }
}
