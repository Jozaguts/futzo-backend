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

    public function regenerateWithFixedRound(
        Tournament $tournament,
        int $roundId,
        array $matches,
        ?int $byeTeamId
    ): array {
        return DB::transaction(function () use ($tournament, $roundId, $matches, $byeTeamId) {
            if ($roundId !== 1) {
                throw new RuntimeException('Esta acción solo permite fijar la jornada 1.');
            }

            $tournament = $tournament->fresh([
                'configuration',
                'league',
                'teams',
                'groupConfiguration',
            ]);

            if (!$tournament || $tournament->teams->count() < 2) {
                throw new RuntimeException('El torneo necesita al menos dos equipos para ajustar el calendario.');
            }

            if ((bool)($tournament->configuration?->group_stage ?? false)) {
                throw new RuntimeException('El ajuste de descanso no aplica cuando el torneo tiene fase de grupos.');
            }

            $hasResults = Game::query()
                ->where('tournament_id', $tournament->id)
                ->where('status', '!=', Game::STATUS_SCHEDULED)
                ->exists();
            if ($hasResults) {
                throw new RuntimeException('No es posible modificar el calendario porque ya existen jornadas con resultados.');
            }

            $teamIds = $tournament->teams->pluck('id')->map(static fn($id) => (int) $id)->all();
            $teamCount = count($teamIds);
            $isOdd = $teamCount % 2 !== 0;
            $expectedMatches = $isOdd ? intdiv($teamCount - 1, 2) : intdiv($teamCount, 2);

            if (count($matches) !== $expectedMatches) {
                throw new RuntimeException('La jornada 1 debe contener la cantidad correcta de partidos.');
            }

            if ($isOdd && !$byeTeamId) {
                throw new RuntimeException('Debes indicar el equipo que descansa en la jornada 1.');
            }

            if (!$isOdd && $byeTeamId) {
                throw new RuntimeException('El descanso solo aplica cuando hay número impar de equipos.');
            }

            $teamMap = array_fill_keys($teamIds, true);
            $usedTeams = [];
            $pairKeys = [];
            $roundPairs = [];

            foreach ($matches as $match) {
                $home = (int) ($match['home_team_id'] ?? 0);
                $away = (int) ($match['away_team_id'] ?? 0);

                if (!$home || !$away || $home === $away) {
                    throw new RuntimeException('Los partidos de la jornada 1 son inválidos.');
                }

                if (!isset($teamMap[$home]) || !isset($teamMap[$away])) {
                    throw new RuntimeException('Todos los equipos de la jornada 1 deben pertenecer al torneo.');
                }

                if (isset($usedTeams[$home]) || isset($usedTeams[$away])) {
                    throw new RuntimeException('Un equipo no puede jugar más de una vez en la jornada 1.');
                }

                $key = $this->buildNormalizedMatchKey($home, $away);
                if (isset($pairKeys[$key])) {
                    throw new RuntimeException('No puedes repetir el mismo partido en la jornada 1.');
                }

                $pairKeys[$key] = true;
                $usedTeams[$home] = true;
                $usedTeams[$away] = true;
                $roundPairs[] = [$home, $away];
            }

            if ($isOdd) {
                $byeTeamId = (int) $byeTeamId;
                if (!isset($teamMap[$byeTeamId])) {
                    throw new RuntimeException('El equipo que descansa no pertenece al torneo.');
                }
                if (isset($usedTeams[$byeTeamId])) {
                    throw new RuntimeException('El equipo que descansa no puede estar en la jornada 1.');
                }
                if (count($usedTeams) !== $teamCount - 1) {
                    throw new RuntimeException('Faltan equipos por asignar en la jornada 1.');
                }
            } elseif (count($usedTeams) !== $teamCount) {
                throw new RuntimeException('La jornada 1 debe incluir a todos los equipos.');
            }

            $roundTrip = (bool)($tournament->configuration?->round_trip ?? false);

            /** @var ScheduleGeneratorService $generator */
            $generator = app(ScheduleGeneratorService::class);
            $fixtureRounds = $generator
                ->setTournament($tournament)
                ->generateFixturesForTeamsWithFixedRound($teamIds, $roundPairs, $roundTrip, $byeTeamId);

            Game::query()
                ->where('tournament_id', $tournament->id)
                ->forceDelete();

            $phaseId = TournamentPhase::query()
                ->where('tournament_id', $tournament->id)
                ->where('is_active', true)
                ->value('id');

            $matchesCreated = 0;
            $currentRound = 1;
            $leagueId = Auth::user()?->league_id ?? $tournament->league_id;

            foreach ($fixtureRounds as $pairings) {
                foreach ($pairings as $pair) {
                    Game::create([
                        'league_id' => $leagueId,
                        'tournament_id' => $tournament->id,
                        'home_team_id' => (int) $pair[0],
                        'away_team_id' => (int) $pair[1],
                        'status' => Game::STATUS_SCHEDULED,
                        'slot_status' => Game::SLOT_STATUS_PENDING,
                        'round' => $currentRound,
                        'match_date' => null,
                        'match_time' => null,
                        'field_id' => null,
                        'location_id' => null,
                        'tournament_phase_id' => $phaseId,
                    ]);
                    $matchesCreated++;
                }

                $currentRound++;
            }

            $result = [
                'cutoff_round' => 1,
                'completed_rounds' => 0,
                'matches_created' => $matchesCreated,
                'message' => 'La jornada 1 fue fijada y el calendario se regeneró desde la jornada 2.',
            ];

            $this->logRegeneration($tournament, $leagueId, 'fixed_round', $result);

            return $result;
        });
    }

    public function regenerateWithForcedBye(Tournament $tournament, int $targetRound, int $byeTeamId): array
    {
        return DB::transaction(function () use ($tournament, $targetRound, $byeTeamId) {
            $tournament = $tournament->fresh([
                'configuration',
                'league',
                'teams',
                'groupConfiguration',
            ]);

            if (!$tournament || $tournament->teams->count() < 2) {
                throw new RuntimeException('El torneo necesita al menos dos equipos para ajustar el calendario.');
            }

            if ((bool)($tournament->configuration?->group_stage ?? false)) {
                throw new RuntimeException('El ajuste de descanso no aplica cuando el torneo tiene fase de grupos.');
            }

            if ($tournament->teams->count() % 2 === 0) {
                throw new RuntimeException('El ajuste de descanso solo aplica cuando el torneo tiene un número impar de equipos.');
            }

            $teamIds = $tournament->teams->pluck('id')->map(static fn($id) => (int) $id)->all();
            if (!in_array($byeTeamId, $teamIds, true)) {
                throw new RuntimeException('El equipo seleccionado no pertenece al torneo.');
            }

            $roundTrip = (bool)($tournament->configuration?->round_trip ?? false);
            $baseRounds = count($teamIds);
            $totalRounds = $roundTrip ? $baseRounds * 2 : $baseRounds;

            if ($targetRound < 1 || $targetRound > $totalRounds) {
                throw new RuntimeException('La jornada solicitada no es válida para este torneo.');
            }

            $hasSchedule = Game::query()
                ->where('tournament_id', $tournament->id)
                ->exists();
            if (!$hasSchedule) {
                throw new RuntimeException('No hay un calendario generado para ajustar.');
            }

            $roundHasResults = Game::query()
                ->where('tournament_id', $tournament->id)
                ->where('round', '>=', $targetRound)
                ->where('status', '!=', Game::STATUS_SCHEDULED)
                ->exists();
            if ($roundHasResults) {
                throw new RuntimeException('No es posible ajustar el descanso porque ya existen jornadas con resultados.');
            }

            $existingKeys = Game::query()
                ->where('tournament_id', $tournament->id)
                ->where('round', '<', $targetRound)
                ->get(['home_team_id', 'away_team_id'])
                ->map(fn($game) => $this->buildMatchKey((int) $game->home_team_id, (int) $game->away_team_id))
                ->all();

            $existingKeyMap = array_fill_keys($existingKeys, true);

            /** @var ScheduleGeneratorService $generator */
            $generator = app(ScheduleGeneratorService::class);
            $offset = $this->resolveRotationOffsetForBye(
                $generator,
                $teamIds,
                $roundTrip,
                $byeTeamId,
                $existingKeyMap
            );

            $fixtureRounds = $generator
                ->setTournament($tournament)
                ->generateFixturesForTeamsWithRotation($teamIds, $roundTrip, $offset);

            Game::query()
                ->where('tournament_id', $tournament->id)
                ->where('round', '>=', $targetRound)
                ->forceDelete();

            $phaseId = TournamentPhase::query()
                ->where('tournament_id', $tournament->id)
                ->where('is_active', true)
                ->value('id');

            $matchesCreated = 0;
            $currentRound = $targetRound;

            foreach ($this->buildRemainingRounds($fixtureRounds, $existingKeyMap) as $pairings) {
                foreach ($pairings as $pair) {
                    Game::create([
                        'league_id' => Auth::user()?->league_id ?? $tournament->league_id,
                        'tournament_id' => $tournament->id,
                        'home_team_id' => (int) $pair[0],
                        'away_team_id' => (int) $pair[1],
                        'status' => Game::STATUS_SCHEDULED,
                        'slot_status' => Game::SLOT_STATUS_PENDING,
                        'round' => $currentRound,
                        'match_date' => null,
                        'match_time' => null,
                        'field_id' => null,
                        'location_id' => null,
                        'tournament_phase_id' => $phaseId,
                    ]);
                    $matchesCreated++;
                }
                $currentRound++;
            }

            $result = [
                'cutoff_round' => $targetRound,
                'completed_rounds' => $targetRound - 1,
                'matches_created' => $matchesCreated,
                'message' => sprintf(
                    'El calendario se ajustó desde la jornada %d para que el equipo %d descanse.',
                    $targetRound,
                    $byeTeamId
                ),
            ];

            $this->logRegeneration($tournament, Auth::user()?->league_id ?? $tournament->league_id, 'bye_override', array_merge($result, [
                'message' => $result['message'],
            ]));

            return $result;
        });
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

    private function buildNormalizedMatchKey(int $teamA, int $teamB): string
    {
        $min = min($teamA, $teamB);
        $max = max($teamA, $teamB);

        return $min . '|' . $max;
    }

    private function resolveRotationOffsetForBye(
        ScheduleGeneratorService $generator,
        array $teamIds,
        bool $roundTrip,
        int $byeTeamId,
        array $existingKeyMap
    ): int {
        $baseRounds = count($teamIds);

        for ($offset = 0; $offset < $baseRounds; $offset++) {
            $fixtureRounds = $generator->generateFixturesForTeamsWithRotation($teamIds, $roundTrip, $offset);
            $remainingRounds = $this->buildRemainingRounds($fixtureRounds, $existingKeyMap);

            if (empty($remainingRounds)) {
                continue;
            }

            $byeTeam = $this->resolveByeTeam($teamIds, $remainingRounds[0]);
            if ($byeTeam === $byeTeamId) {
                return $offset;
            }
        }

        throw new RuntimeException('No se pudo ajustar el descanso solicitado con las jornadas actuales.');
    }

    private function buildRemainingRounds(array $fixtureRounds, array $existingKeyMap): array
    {
        $remainingRounds = [];

        foreach ($fixtureRounds as $pairings) {
            $filtered = [];
            foreach ($pairings as $pair) {
                if (!is_array($pair) || count($pair) < 2) {
                    continue;
                }

                $key = $this->buildMatchKey((int) $pair[0], (int) $pair[1]);
                if (isset($existingKeyMap[$key])) {
                    continue;
                }

                $filtered[] = [(int) $pair[0], (int) $pair[1]];
            }

            if (!empty($filtered)) {
                $remainingRounds[] = $filtered;
            }
        }

        return $remainingRounds;
    }

    private function resolveByeTeam(array $teamIds, array $pairings): ?int
    {
        $playing = [];

        foreach ($pairings as $pair) {
            if (!is_array($pair) || count($pair) < 2) {
                continue;
            }

            $playing[] = (int) $pair[0];
            $playing[] = (int) $pair[1];
        }

        $missing = array_values(array_diff($teamIds, array_unique($playing)));

        return count($missing) === 1 ? (int) $missing[0] : null;
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
