<?php

namespace App\Actions\Tournament;

use App\Models\Tournament;

final class GetTournamentStandingsAction {
    public function execute(Tournament $tournament): array
    {
        $tournament->loadMissing(['format', 'tournamentPhases.phase']);

        $fallbackPhaseName = $tournament->format?->name === 'Grupos y Eliminatoria'
            ? 'Fase de grupos'
            : 'Tabla general';

        $fallbackPhase = $tournament->tournamentPhases
            ->first(static fn($phase) => $phase->phase?->name === $fallbackPhaseName);

        $activePhase = $tournament->activePhase();
        $targetPhaseId = $fallbackPhase?->id ?? $activePhase?->id;

        $query = $tournament
            ->standings()
            ->with('team')
            ->orderByRaw('CASE WHEN matches_played = 0 THEN 1 ELSE 0 END')
            ->orderBy('rank');

        if (is_null($targetPhaseId)) {
            $query->whereNull('tournament_phase_id');
        } else {
            $query->where('tournament_phase_id', $targetPhaseId);
        }

        return $query
            ->get()
            ->toArray();
    }
}
