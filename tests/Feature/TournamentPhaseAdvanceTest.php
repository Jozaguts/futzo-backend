<?php

use App\Enums\TournamentFormatId;
use App\Models\Game;
use App\Models\TournamentFieldReservation;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

function buildLeagueEliminationSchedulePayload($tournament, $location, $field, string $startDateIso): array
{
    $phases = $tournament->tournamentPhases
        ->load('phase')
        ->map(function ($tournamentPhase) use ($tournament) {
            $rules = null;
            if ($tournamentPhase->phase->name !== 'Tabla general') {
                $rules = [
                    'round_trip' => false,
                    'away_goals' => false,
                    'extra_time' => true,
                    'penalties' => true,
                    'advance_if_tie' => 'better_seed',
                ];
            }

            return [
                'tournament_id' => $tournament->id,
                'id' => $tournamentPhase->phase->id,
                'name' => $tournamentPhase->phase->name,
                'is_active' => $tournamentPhase->is_active,
                'is_completed' => $tournamentPhase->is_completed,
                'rules' => $rules,
            ];
        })->all();

    return [
        'general' => [
            'tournament_id' => $tournament->id,
            'tournament_format_id' => $tournament->configuration->tournament_format_id,
            'football_type_id' => 1,
            'start_date' => $startDateIso,
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => $tournament->teams()->count(),
            'locations' => [['id' => $location->id, 'name' => $location->name]],
        ],
        'rules_phase' => [
            'round_trip' => false,
            'tiebreakers' => $tournament->configuration->tiebreakers->toArray(),
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => false,
            'phases' => $phases,
        ],
        'fields_phase' => [[
            'field_id' => $field->id,
            'step' => 1,
            'field_name' => $field->name,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'disabled' => false,
            'availability' => [
                'friday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 23:00',
                    'intervals' => array_map(
                        fn($hour) => [
                            'value' => sprintf('%02d:00', $hour),
                            'text' => sprintf('%02d:00', $hour),
                            'selected' => true,
                            'disabled' => false,
                        ],
                        range(9, 22)
                    ),
                    'label' => 'Viernes',
                ],
                'isCompleted' => true,
            ],
        ]],
    ];
}

it('no permite avanzar de fase mientras existen partidos pendientes', function () {
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::LeagueAndElimination->value, 1, null, null);
    attachTeamsToTournament($tournament, 8);

    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $payload = buildLeagueEliminationSchedulePayload($tournament->refresh(), $location, $field, $startDate);

    $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/schedule", $payload)->assertOk();

    $response = $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/phases/advance");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Aún hay partidos pendientes en la fase actual.']);
});

it('avanza de la fase Tabla general a la siguiente y libera reservas', function () {
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::LeagueAndElimination->value, 1, null, null);
    attachTeamsToTournament($tournament, 8);

    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $payload = buildLeagueEliminationSchedulePayload($tournament->refresh(), $location, $field, $startDate);

    $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/schedule", $payload)->assertOk();

    expect(TournamentFieldReservation::where('tournament_id', $tournament->id)->exists())->toBeTrue();

    Game::where('tournament_id', $tournament->id)->update([
        'home_goals' => 1,
        'away_goals' => 0,
        'status' => Game::STATUS_COMPLETED,
    ]);

    $response = $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/phases/advance");

    $response->assertOk()
        ->assertJsonPath('current_phase.is_completed', 1)
        ->assertJsonPath('next_phase.phase.name', 'Cuartos de Final');

    expect(TournamentFieldReservation::where('tournament_id', $tournament->id)->exists())->toBeFalse();

    $tournament->refresh();
    $activePhase = $tournament->tournamentPhases()->where('is_active', true)->with('phase')->first();
    expect($activePhase?->phase?->name)->toBe('Cuartos de Final');
});

it('finaliza el torneo y marca al campeón cuando no hay más fases', function () {
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::LeagueAndElimination->value, 1, null, null);
    attachTeamsToTournament($tournament, 8);

    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $payload = buildLeagueEliminationSchedulePayload($tournament->refresh(), $location, $field, $startDate);

    $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/schedule", $payload)->assertOk();

    $tournament->refresh();

    $maxIterations = 5;
    $expectedChampionTeamId = null;
    $expectedChampionName = null;

    for ($i = 0; $i < $maxIterations; $i++) {
        $tournament->refresh();
        $activePhase = $tournament->activePhase();
        expect($activePhase)->not->toBeNull();

        $gamesQuery = Game::where('tournament_id', $tournament->id)
            ->where('tournament_phase_id', $activePhase->id);

        if ($activePhase->phase?->name === 'Final' && !$gamesQuery->exists()) {
            $teams = $tournament->teams()->take(2)->get()->values();
            $fieldModel = $location->fields()->first();
            Game::create([
                'tournament_id' => $tournament->id,
                'league_id' => $tournament->league_id,
                'home_team_id' => $teams[0]->id,
                'away_team_id' => $teams[1]->id,
                'field_id' => $fieldModel?->id,
                'location_id' => $location->id,
                'round' => 99,
                'match_date' => now()->toDateString(),
                'match_time' => now()->format('H:i:s'),
                'status' => Game::STATUS_SCHEDULED,
                'tournament_phase_id' => $activePhase->id,
            ]);
        }

        $games = $gamesQuery->get();

        foreach ($games as $game) {
            $game->home_goals = 2;
            $game->away_goals = 1;
            $game->status = Game::STATUS_COMPLETED;
            $game->winner_team_id = $game->home_team_id;
            $game->save();
        }

        if ($activePhase->phase?->name === 'Final') {
            $finalGame = $games->first();
            expect($finalGame)->not->toBeNull();
            $expectedChampionTeamId = $finalGame->home_team_id;
            $expectedChampionName = $finalGame->homeTeam->name;
        }

        expect(
            Game::where('tournament_id', $tournament->id)
                ->where('tournament_phase_id', $activePhase->id)
                ->where('status', '!=', Game::STATUS_COMPLETED)
                ->exists()
        )->toBeFalse();

        $response = $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/phases/advance");

        if ($response->json('next_phase') === null) {
            $response->assertOk()
                ->assertJsonPath('champion.team_id', $expectedChampionTeamId)
                ->assertJsonPath('tournament.status', 'completado')
                ->assertJsonPath('tournament.winner', $expectedChampionName);
            expect($response->json('message'))->toContain('Torneo finalizado');
            break;
        }
    }

    $tournament->refresh();
    expect($tournament->status)->toBe('completado');
    expect($tournament->winner)->toBe($expectedChampionName);
});
