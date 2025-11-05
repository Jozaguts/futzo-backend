<?php

namespace App\Services;

use App\Enums\TournamentFormatId;
use App\Models\Phase;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TournamentPhaseService
{
    private const FALLBACK_PHASE = 'Tabla general';
    private const FORMAT_PHASES = [
        TournamentFormatId::League->value => ['Tabla general'],
        TournamentFormatId::LeagueAndElimination->value => [
            'Tabla general',
            'Dieciseisavos de Final',
            'Octavos de Final',
            'Cuartos de Final',
            'Semifinales',
            'Final',
        ],
        TournamentFormatId::GroupAndElimination->value => [
            'Fase de grupos',
            'Dieciseisavos de Final',
            'Octavos de Final',
            'Cuartos de Final',
            'Semifinales',
            'Final',
        ],
        TournamentFormatId::Elimination->value => [
            'Dieciseisavos de Final',
            'Octavos de Final',
            'Cuartos de Final',
            'Semifinales',
            'Final',
        ],
        TournamentFormatId::Swiss->value => ['Tabla general'],
    ];

    /**
     * Synchronize tournament phases with the expected configuration for the given format.
     */
    public function sync(Tournament $tournament): void
    {
        $phaseNames = collect(self::FORMAT_PHASES[$tournament->tournament_format_id] ?? [self::FALLBACK_PHASE]);
        $phases = Phase::whereIn('name', $phaseNames)->get()->keyBy('name');

        DB::transaction(function () use ($tournament, $phaseNames, $phases) {
            $currentActivePhaseId = $tournament->tournamentPhases()
                ->where('is_active', true)
                ->value('phase_id');

            $allowedPhaseIds = $this->resolveAllowedPhaseIds($phaseNames, $phases);
            if ($allowedPhaseIds->isEmpty()) {
                return;
            }

            $this->purgeDisallowedPhases($tournament, $allowedPhaseIds);
            $this->ensurePhasesExist($tournament, $phaseNames, $phases);
            $this->ensureActivePhase($tournament, $allowedPhaseIds, $currentActivePhaseId);
        });

        if ($tournament->tournament_format_id !== TournamentFormatId::GroupAndElimination->value) {
            optional($tournament->groupConfiguration)->delete();

            DB::table('team_tournament')
                ->where('tournament_id', $tournament->id)
                ->update(['group_key' => null]);
        }
    }

    /**
     * @param Collection<int, string> $phaseNames
     * @param Collection<string, Phase> $phases
     */
    private function resolveAllowedPhaseIds(Collection $phaseNames, Collection $phases): Collection
    {
        return $phaseNames
            ->map(fn(string $name) => optional($phases->get($name))->id)
            ->filter()
            ->values();
    }

    private function purgeDisallowedPhases(Tournament $tournament, Collection $allowedPhaseIds): void
    {
        $tournament->tournamentPhases()
            ->with(['rules'])
            ->whereNotIn('phase_id', $allowedPhaseIds->all())
            ->get()
            ->each(function (TournamentPhase $phase) {
                $phase->rules()?->delete();
                $phase->delete();
            });
    }

    /**
     * @param Collection<int, string> $phaseNames
     * @param Collection<string, Phase> $phases
     */
    private function ensurePhasesExist(Tournament $tournament, Collection $phaseNames, Collection $phases): void
    {
        $phaseNames->each(function (string $phaseName, int $index) use ($tournament, $phases) {
            $phase = $phases->get($phaseName);
            if (!$phase) {
                return;
            }

            /** @var TournamentPhase $tournamentPhase */
            $tournamentPhase = $tournament->tournamentPhases()
                ->withTrashed()
                ->firstOrCreate(
                    ['phase_id' => $phase->id],
                    [
                        'is_active' => false,
                        'is_completed' => false,
                    ]
                );

            if ($tournamentPhase->trashed()) {
                $tournamentPhase->restore();
                $tournamentPhase->update(['is_completed' => false]);
            }
        });
    }

    private function ensureActivePhase(Tournament $tournament, Collection $allowedPhaseIds, ?int $preferredPhaseId): void
    {
        $activePhase = $tournament->tournamentPhases()->where('is_active', true)->first();
        if ($activePhase && $allowedPhaseIds->contains($activePhase->phase_id)) {
            return;
        }

        $targetPhaseId = null;
        if ($preferredPhaseId !== null && $allowedPhaseIds->contains($preferredPhaseId)) {
            $targetPhaseId = $preferredPhaseId;
        } else {
            $targetPhaseId = $allowedPhaseIds->first();
        }

        if ($targetPhaseId === null) {
            return;
        }

        $tournament->tournamentPhases()->update(['is_active' => false]);
        $tournament->tournamentPhases()
            ->where('phase_id', $targetPhaseId)
            ->update([
                'is_active' => true,
                'is_completed' => false,
            ]);
    }
}
