<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Services\TournamentScheduleRegenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

/**
 * Regenera el calendario desde una jornada específica forzando el descanso (bye) de un equipo.
 *
 * Este job:
 * - Valida que el torneo tenga número impar de equipos.
 * - Verifica que no existan resultados en jornadas >= la solicitada.
 * - Busca la rotación correcta para que el equipo indicado descanse en esa jornada.
 * - Elimina partidos desde la jornada indicada y crea los nuevos partidos.
 * - Registra un log de regeneración con modo "bye_override".
 *
 * Nota: actualmente este flujo no se está utilizando en la UI.
 */
class RegenerateScheduleWithForcedByeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tournamentId,
        public int $roundId,
        public int $byeTeamId,
        public ?int $userId,
    ) {
    }

    public function handle(TournamentScheduleRegenerationService $service): void
    {
        if ($this->userId) {
            Auth::loginUsingId($this->userId);
        }

        $tournament = Tournament::findOrFail($this->tournamentId);
        $service->regenerateWithForcedBye($tournament, $this->roundId, $this->byeTeamId);

        if ($this->userId) {
            Auth::logout();
        }
    }
}
