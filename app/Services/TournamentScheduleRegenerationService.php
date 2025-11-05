<?php

namespace App\Services;

use App\Models\Game;
use App\Models\ScheduleRegenerationLog;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TournamentScheduleRegenerationService
{
    public function canUpdateStartDate(Tournament $tournament): bool
    {
        $games = Game::query()
            ->where('tournament_id', $tournament->id)
            ->orderBy('round')
            ->get(['round', 'status']);

        if ($games->isEmpty()) {
            return true;
        }

        $allScheduled = $games->every(static fn($game) => $game->status === Game::STATUS_SCHEDULED);

        $firstRound = $games->min('round');
        if ($firstRound === null) {
            return true;
        }

        $firstRoundHasStarted = $games
            ->where('round', $firstRound)
            ->contains(static fn($game) => $game->status !== Game::STATUS_SCHEDULED);

        if ($allScheduled) {
            return true;
        }

        return !$firstRoundHasStarted;
    }

    public function analyze(Tournament $tournament, ?bool $roundTripOverride = null): array
    {
        $games = Game::query()
            ->where('tournament_id', $tournament->id)
            ->orderBy('round')
            ->get(['id', 'round', 'status']);

        $roundTrip = $roundTripOverride ?? (bool)($tournament->configuration?->round_trip ?? false);

        if ($games->isEmpty()) {
            return $this->augmentAnalysis($tournament, [
                'mode' => 'full',
                'cutoff_round' => null,
                'completed_rounds' => 0,
                'total_rounds' => 0,
                'pending_manual_matches' => 0,
                'explanation' => 'No hay partidos programados. El calendario se regenerará completamente.',
            ], $roundTrip);
        }

        $roundGroups = $games->groupBy('round')->sortKeys();
        $maxRound = (int)($roundGroups->keys()->map(static fn($round) => (int)$round)->max() ?? 0);
        $completedRounds = $this->resolveCompletedRounds($roundGroups);

        if (!$games->contains('status', Game::STATUS_COMPLETED)) {
            return $this->augmentAnalysis($tournament, [
                'mode' => 'full',
                'cutoff_round' => null,
                'completed_rounds' => 0,
                'total_rounds' => $maxRound,
                'pending_manual_matches' => $this->countPendingManualMatches($tournament->id),
                'explanation' => 'No hay partidos completados. El calendario se regenerará completamente.',
            ], $roundTrip);
        }

        if ($completedRounds === 0) {
            return $this->augmentAnalysis($tournament, [
                'mode' => 'full',
                'cutoff_round' => null,
                'completed_rounds' => 0,
                'total_rounds' => $maxRound,
                'pending_manual_matches' => $this->countPendingManualMatches($tournament->id),
                'explanation' => 'Aún no hay jornadas completadas. El calendario se regenerará completamente.',
            ], $roundTrip);
        }

        if ($completedRounds >= $maxRound) {
            return $this->augmentAnalysis($tournament, [
                'mode' => 'full',
                'cutoff_round' => null,
                'completed_rounds' => $completedRounds,
                'total_rounds' => $maxRound,
                'pending_manual_matches' => $this->countPendingManualMatches($tournament->id),
                'explanation' => 'Todas las jornadas están completadas. No hay partidos pendientes para regenerar.',
            ], $roundTrip);
        }

        $cutoffRound = $completedRounds + 1;

        return $this->augmentAnalysis($tournament, [
            'mode' => 'partial',
            'cutoff_round' => $cutoffRound,
            'completed_rounds' => $completedRounds,
            'total_rounds' => $maxRound,
            'pending_manual_matches' => $this->countPendingManualMatches($tournament->id),
            'explanation' => sprintf(
                'Se detectaron partidos completados hasta la jornada %d. El calendario se regenerará a partir de la jornada %d.',
                $completedRounds,
                $cutoffRound
            ),
        ], $roundTrip);
    }

    public function regenerate(Tournament $tournament, array $analysis): array
    {
        $mode = $analysis['mode'] ?? null;
        if (!in_array($mode, ['full', 'partial'], true)) {
            throw new RuntimeException('Acción de regeneración inválida.');
        }

        return DB::transaction(function () use ($tournament, $analysis, $mode) {
            $leagueId = Auth::user()?->league_id ?? $tournament->league_id;

            if ($mode === 'full') {
                $result = $this->regenerateFull($tournament, $leagueId);
            } else {
                $cutoffRound = (int)($analysis['cutoff_round'] ?? 0);
                $completedRounds = (int)($analysis['completed_rounds'] ?? max(0, $cutoffRound - 1));
                $result = $this->regeneratePartial($tournament, $leagueId, $cutoffRound, $completedRounds);
            }

            $pendingManualMatches = $this->countPendingManualMatches($tournament->id);
            $log = $this->logRegeneration($tournament, $leagueId, $mode, $result);

            return array_merge($result, [
                'mode' => $mode,
                'pending_manual_matches' => $pendingManualMatches,
                'log_id' => $log->id,
            ]);
        });
    }

    private function resolveCompletedRounds(Collection $roundGroups): int
    {
        $completedRounds = 0;

        foreach ($roundGroups as $round => $matches) {
            if (!$matches instanceof Collection) {
                $matches = collect($matches);
            }

            if ($matches->every(static fn($game) => $game->status === Game::STATUS_COMPLETED)) {
                $completedRounds = (int)$round;
            } else {
                break;
            }
        }

        return $completedRounds;
    }

    private function regenerateFull(Tournament $tournament, int $leagueId): array
    {
        Game::query()
            ->where('tournament_id', $tournament->id)
            ->forceDelete();

        $tournament = $tournament->fresh([
            'configuration',
            'league',
            'teams',
            'tournamentPhases.phase',
            'groupConfiguration',
        ]);

        if (!$tournament || $tournament->teams->count() < 2) {
            throw new RuntimeException('El torneo necesita al menos dos equipos para generar el calendario.');
        }

        /** @var ScheduleGeneratorService $generator */
        $generator = app(ScheduleGeneratorService::class);
        $matches = $generator
            ->setTournament($tournament)
            ->makeSchedule();

        if (empty($matches)) {
            return [
                'cutoff_round' => null,
                'completed_rounds' => 0,
                'matches_created' => 0,
                'message' => 'No se generaron partidos nuevos para el calendario.',
            ];
        }

        $generator->persistScheduleToMatchSchedules($matches);

        $roundCount = collect($matches)->pluck('round')->unique()->count();
        $matchCount = count($matches);

        return [
            'cutoff_round' => null,
            'completed_rounds' => 0,
            'matches_created' => $matchCount,
            'message' => sprintf(
                'Calendario regenerado completamente. Se programaron %d partidos en %d jornadas.',
                $matchCount,
                $roundCount
            ),
        ];
    }

    private function regeneratePartial(
        Tournament $tournament,
        int $leagueId,
        int $cutoffRound,
        int $completedRounds
    ): array {
        if ($cutoffRound < 1) {
            throw new RuntimeException('No se pudo determinar la jornada de corte para regenerar el calendario.');
        }

        Game::query()
            ->where('tournament_id', $tournament->id)
            ->where('round', '>=', $cutoffRound)
            ->where('status', '!=', Game::STATUS_COMPLETED)
            ->forceDelete();

        $keptMatches = Game::query()
            ->where('tournament_id', $tournament->id)
            ->get(['home_team_id', 'away_team_id', 'status', 'round']);

        $existingKeys = $keptMatches
            ->map(fn($game) => $this->buildMatchKey((int)$game->home_team_id, (int)$game->away_team_id))
            ->all();

        $tournament = $tournament->fresh([
            'configuration',
            'league',
            'teams',
            'groupConfiguration',
        ]);

        if (!$tournament || $tournament->teams->count() < 2) {
            throw new RuntimeException('El torneo necesita al menos dos equipos para generar el calendario.');
        }

        $teamIds = $tournament->teams->pluck('id')->map(static fn($id) => (int)$id)->all();
        $roundTrip = (bool)($tournament->configuration?->round_trip ?? false);

        /** @var ScheduleGeneratorService $generator */
        $generator = app(ScheduleGeneratorService::class);
        $fixtureRounds = $generator
            ->setTournament($tournament)
            ->generateFixturesForTeams($teamIds, $roundTrip);

        $newMatchesByOriginalRound = [];

        foreach ($fixtureRounds as $roundIndex => $pairings) {
            foreach ($pairings as $pair) {
                if (!is_array($pair) || count($pair) < 2) {
                    continue;
                }

                [$home, $away] = $pair;
                if (is_null($home) || is_null($away)) {
                    continue;
                }

                $key = $this->buildMatchKey((int)$home, (int)$away);
                if (in_array($key, $existingKeys, true)) {
                    continue;
                }

                $existingKeys[] = $key;
                $newMatchesByOriginalRound[$roundIndex + 1][] = [
                    'home_team_id' => (int)$home,
                    'away_team_id' => (int)$away,
                ];
            }
        }

        if (empty($newMatchesByOriginalRound)) {
            return [
                'cutoff_round' => $cutoffRound,
                'completed_rounds' => $completedRounds,
                'matches_created' => 0,
                'message' => 'No se detectaron partidos pendientes para regenerar.',
            ];
        }

        $phaseId = TournamentPhase::query()
            ->where('tournament_id', $tournament->id)
            ->where('is_active', true)
            ->value('id');

        $matchesCreated = 0;
        $currentRound = $cutoffRound;

        foreach (collect($newMatchesByOriginalRound)->sortKeys() as $pairings) {
            if (empty($pairings)) {
                continue;
            }

            foreach ($pairings as $pair) {
                Game::create([
                    'league_id' => $leagueId,
                    'tournament_id' => $tournament->id,
                    'home_team_id' => $pair['home_team_id'],
                    'away_team_id' => $pair['away_team_id'],
                    'status' => Game::STATUS_SCHEDULED,
                    'slot_status' => Game::SLOT_STATUS_PENDING,
                    'round' => $currentRound,
                    'match_date' => null,
                    'match_time' => null,
                    'field_id' => null,
                    'location_id' => null,
                    'tournament_phase_id' => $phaseId,
                    'starts_at_utc' => null,
                    'ends_at_utc' => null,
                ]);
                $matchesCreated++;
            }

            $currentRound++;
        }

        return [
            'cutoff_round' => $cutoffRound,
            'completed_rounds' => $completedRounds,
            'matches_created' => $matchesCreated,
            'message' => sprintf(
                'El calendario fue regenerado a partir de la jornada %d. Los partidos anteriores se mantienen intactos. Los nuevos partidos se crearon sin horario ni campo asignado.',
                $cutoffRound
            ),
        ];
    }

    private function buildMatchKey(int $homeTeamId, int $awayTeamId): string
    {
        return $homeTeamId . '|' . $awayTeamId;
    }

    private function countPendingManualMatches(int $tournamentId): int
    {
        return Game::query()
            ->where('tournament_id', $tournamentId)
            ->where(static function ($query) {
                $query->whereNull('match_date')
                    ->orWhereNull('field_id');
            })
            ->count();
    }

    private function augmentAnalysis(Tournament $tournament, array $analysis, bool $roundTrip): array
    {
        $totalTeams = $this->resolveTeamCount($tournament);
        $hasBye = $totalTeams > 0 && ($totalTeams % 2 !== 0);
        $matchesPerRound = $totalTeams < 2 ? 0 : intdiv($totalTeams, 2);
        if ($totalTeams > 1 && $hasBye) {
            $matchesPerRound = intdiv($totalTeams - 1, 2);
        }
        $baseRounds = 0;
        if ($totalTeams > 1) {
            $baseRounds = $hasBye ? $totalTeams : ($totalTeams - 1);
        }
        $projectedRounds = $roundTrip ? $baseRounds * 2 : $baseRounds;
        $totalMatches = $matchesPerRound * $projectedRounds;

        $message = $roundTrip
            ? 'El calendario regular se generará con partidos de ida y vuelta.'
            : 'El calendario regular se generará con partidos solo de ida.';

        return array_merge($analysis, [
            'round_trip_selected' => $roundTrip,
            'round_trip_message' => $message,
            'matches_per_round' => $matchesPerRound,
            'projected_rounds' => $projectedRounds,
            'total_matches' => $totalMatches,
            'has_bye' => $hasBye,
        ]);
    }

    private function resolveTeamCount(Tournament $tournament): int
    {
        if ($tournament->relationLoaded('teams')) {
            return (int)$tournament->teams->count();
        }

        if (isset($tournament->teams_count)) {
            return (int)$tournament->teams_count;
        }

        return (int)$tournament->teams()->count();
    }

    private function logRegeneration(Tournament $tournament, int $leagueId, string $mode, array $result): ScheduleRegenerationLog
    {
        return ScheduleRegenerationLog::create([
            'league_id' => $leagueId,
            'tournament_id' => $tournament->id,
            'user_id' => Auth::id(),
            'mode' => $mode,
            'cutoff_round' => $result['cutoff_round'] ?? null,
            'completed_rounds' => $result['completed_rounds'] ?? 0,
            'matches_created' => $result['matches_created'] ?? 0,
            'meta' => [
                'message' => $result['message'] ?? null,
            ],
        ]);
    }
}
