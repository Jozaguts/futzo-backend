<?php

namespace App\Actions;

use App\Http\Resources\TournamentScheduleCollection;
use App\Models\Game;
use App\Models\ScheduleRegenerationLog;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

readonly class BuildTournamentScheduleAction
{
    public function __construct(
        private HydrateGroupDataForGamesAction $hydrateGroupData
    ) {
    }

    public function execute(Tournament|int $tournament, Request $request, int $perPage = 1): array
    {
        $tournament = $this->resolveTournament($tournament);

        $filterBy = $request->get('filterBy', false);
        $search = $request->get('search', false);
        $page = (int) $request->get('page', 1);
        $skip = ($page - 1) * $perPage;

        $activePhase = $tournament->activePhase();

        $baseQuery = Game::query()
            ->where('tournament_id', $tournament->id)
            ->when($activePhase, static function ($query, $phase) {
                $query->where('tournament_phase_id', $phase->id);
            })
            ->when(!$activePhase, static function ($query) {
                $query->whereNull('tournament_phase_id');
            });

        $hasSchedule = (clone $baseQuery)->exists();

        $schedule = collect();
        $totalRounds = 0;

        if ($hasSchedule) {
            $filteredQuery = (clone $baseQuery)
                ->when($filterBy, static function ($query, $status) {
                    $query->where('status', $status);
                })
                ->when($search, static function ($query, $term) {
                    $query->where(static function ($nested) use ($term) {
                        $nested->whereHas('awayTeam', static fn($teamQuery) => $teamQuery->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('homeTeam', static fn($teamQuery) => $teamQuery->where('name', 'like', "%{$term}%"));
                    });
                })
                ->orderBy('round');

            $totalRounds = (clone $filteredQuery)
                ->distinct('round')
                ->count('round');

            $schedule = (clone $filteredQuery)
                ->with([
                    'homeTeam',
                    'awayTeam',
                    'field',
                    'location',
                    'referee',
                    'tournament',
                    'tournament.configuration',
                    // Pre-cargamos la plantilla de equipos para identificar al club que descansa en jornadas impares.
                    'tournament.teams:id,name,image',
                    'tournamentPhase',
                    'tournamentPhase.phase',
                ])
                ->get()
                ->groupBy('round')
                ->slice($skip, $perPage)
                ->flatten();

            $schedule = $this->hydrateGroupData->execute($tournament, $schedule);
        }

        $latestLog = ScheduleRegenerationLog::query()
            ->where('tournament_id', $tournament->id)
            ->latest()
            ->first();

        $pendingManualMatches = Game::query()
            ->where('tournament_id', $tournament->id)
            ->where(static function ($query) {
                $query->whereNull('match_date')
                    ->orWhereNull('field_id');
            })
            ->count();

        return [
            'rounds' => TournamentScheduleCollection::make($schedule)->toArray($request),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_rounds' => $totalRounds,
            ],
            'hasSchedule' => $hasSchedule,
            'regeneration' => $latestLog ? [
                'mode' => $latestLog->mode,
                'cutoff_round' => $latestLog->cutoff_round,
                'executed_at' => optional($latestLog->created_at)->toIso8601String(),
            ] : null,
            'pending_manual_matches' => $pendingManualMatches,
        ];
    }

    private function resolveTournament(Tournament|int $tournament): Tournament
    {
        if ($tournament instanceof Tournament) {
            $tournament->loadMissing(['configuration']);
            return $tournament;
        }

        return Tournament::with(['configuration'])->findOrFail($tournament);
    }
}
