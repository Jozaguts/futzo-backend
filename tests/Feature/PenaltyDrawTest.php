<?php

use App\Models\Category;
use App\Models\Country;
use App\Models\FootballType;
use App\Models\Game;
use App\Models\League;
use App\Models\Location;
use App\Models\Phase;
use App\Models\Standing;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use App\Models\TournamentFormat;
use App\Services\StandingsService;
use Carbon\Carbon;

it('requires penalty data for draws when the tournament enforces shootouts', function () {
    [$tournament, $location, $teams, $game] = createPenaltyMatchSetup();

    $response = $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/rounds/{$game->round}", [
        'matches' => [
            [
                'id' => $game->id,
                'home' => ['id' => $teams[0]->id, 'goals' => 1],
                'away' => ['id' => $teams[1]->id, 'goals' => 1],
            ],
        ],
    ]);

    $response->assertStatus(422);
});

it('stores penalty shootout details for tied games when enabled', function () {
    [$tournament, $location, $teams, $game] = createPenaltyMatchSetup();

    $payload = [
        'matches' => [
            [
                'id' => $game->id,
                'home' => ['id' => $teams[0]->id, 'goals' => 2],
                'away' => ['id' => $teams[1]->id, 'goals' => 2],
                'penalties' => [
                    'decided' => true,
                    'winner_team_id' => $teams[0]->id,
                    'home_goals' => 5,
                    'away_goals' => 4,
                ],
            ],
        ],
    ];

    $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/rounds/{$game->round}", $payload)
        ->assertOk();

    $game->refresh();

    expect($game->decided_by_penalties)->toBeTrue()
        ->and($game->penalty_winner_team_id)->toBe($teams[0]->id)
        ->and($game->penalty_home_goals)->toBe(5)
        ->and($game->penalty_away_goals)->toBe(4)
        ->and($game->winner_team_id)->toBe($teams[0]->id);
});

it('awards two and one points on standings for penalty shootout results', function () {
    [$tournament, $location, $teams, $game] = createPenaltyMatchSetup();

    $payload = [
        'matches' => [
            [
                'id' => $game->id,
                'home' => ['id' => $teams[0]->id, 'goals' => 3],
                'away' => ['id' => $teams[1]->id, 'goals' => 3],
                'penalties' => [
                    'decided' => true,
                    'winner_team_id' => $teams[1]->id,
                    'home_goals' => 6,
                    'away_goals' => 7,
                ],
            ],
        ],
    ];

    $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/rounds/{$game->round}", $payload)
        ->assertOk();

    app(StandingsService::class)->recalculateStandingsForPhase(
        $tournament->id,
        $game->tournament_phase_id,
        $game->id
    );

    $standings = Standing::where('tournament_id', $tournament->id)->get()->keyBy('team_id');

    expect($standings[$teams[1]->id]->points)->toBe(2)
        ->and($standings[$teams[1]->id]->wins)->toBe(1)
        ->and($standings[$teams[0]->id]->points)->toBe(1)
        ->and($standings[$teams[0]->id]->losses)->toBe(1);
});

it('keeps classic draw behaviour when the penalty flag is disabled', function () {
    [$tournament, $location, $teams, $game] = createPenaltyMatchSetup(false);

    $payload = [
        'matches' => [
            [
                'id' => $game->id,
                'home' => ['id' => $teams[0]->id, 'goals' => 1],
                'away' => ['id' => $teams[1]->id, 'goals' => 1],
            ],
        ],
    ];

    $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/rounds/{$game->round}", $payload)
        ->assertOk();

    $game->refresh();

    expect($game->decided_by_penalties)->toBeFalse()
        ->and($game->penalty_winner_team_id)->toBeNull();

    app(StandingsService::class)->recalculateStandingsForPhase(
        $tournament->id,
        $game->tournament_phase_id,
        $game->id
    );
    $standings = Standing::where('tournament_id', $tournament->id)->get()->keyBy('team_id');

    expect($standings[$teams[0]->id]->points)->toBe(1)
        ->and($standings[$teams[1]->id]->points)->toBe(1);
});

it('ignores penalty payload for elimination phase games', function () {
    [$tournament, $location, $teams, $game] = createPenaltyMatchSetup();

    $phase = Phase::firstOrCreate(
        ['name' => 'Cuartos de Final'],
        ['is_active' => false, 'is_completed' => false, 'min_teams_for' => 8]
    );

    $tournamentPhase = TournamentPhase::create([
        'phase_id' => $phase->id,
        'tournament_id' => $tournament->id,
        'is_active' => false,
        'is_completed' => false,
    ]);

    $game->update(['tournament_phase_id' => $tournamentPhase->id]);

    $payload = [
        'matches' => [
            [
                'id' => $game->id,
                'home' => ['id' => $teams[0]->id, 'goals' => 0],
                'away' => ['id' => $teams[1]->id, 'goals' => 0],
                'penalties' => [
                    'decided' => true,
                    'winner_team_id' => $teams[0]->id,
                    'home_goals' => 4,
                    'away_goals' => 3,
                ],
            ],
        ],
    ];

    $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/rounds/{$game->round}", $payload)
        ->assertOk();

    $game->refresh();

    expect($game->decided_by_penalties)->toBeFalse()
        ->and($game->penalty_winner_team_id)->toBeNull()
        ->and($game->penalty_home_goals)->toBeNull()
        ->and($game->penalty_away_goals)->toBeNull();
});

/**
 * @return array{Tournament, \App\Models\Location, \Illuminate\Support\Collection<int,\App\Models\Team>, Game}
 */
function createPenaltyMatchSetup(bool $penaltyFlag = true): array
{
    $country = Country::query()->firstOrCreate(
        ['iso_code' => 'TC'],
        ['name' => 'Test Country']
    );

    $footballTypeData = config('constants.football_types')[0];
    $footballType = FootballType::query()->firstOrCreate(
        ['id' => $footballTypeData['id']],
        $footballTypeData
    );

    $league = League::factory()->create([
        'country_id' => $country->id,
        'football_type_id' => $footballType->id,
    ]);

    if (auth()->check() && auth()->user()->league_id !== $league->id) {
        auth()->user()->forceFill(['league_id' => $league->id])->saveQuietly();
    }

    $category = Category::factory()->create();

    $formatData = config('constants.tournament_formats')[0];
    $tournamentFormat = TournamentFormat::query()->firstOrCreate(
        ['id' => $formatData['id']],
        $formatData
    );

    $locationData = config('constants.location');
    $location = Location::query()->firstOrCreate(
        ['id' => $locationData['id']],
        $locationData
    );

    $tournament = Tournament::factory()->create([
        'league_id' => $league->id,
        'category_id' => $category->id,
        'tournament_format_id' => $tournamentFormat->id,
        'football_type_id' => $footballType->id,
        'penalty_draw_enabled' => $penaltyFlag,
    ]);

    $tournament->locations()->attach($location->id);

    attachTeamsToTournament($tournament, 2);

    $teams = $tournament->teams()->take(2)->get()->values();

    $generalPhase = Phase::firstOrCreate(
        ['name' => 'Tabla general'],
        ['is_active' => true, 'is_completed' => false, 'min_teams_for' => null]
    );

    $tournamentPhase = TournamentPhase::firstOrCreate(
        [
            'phase_id' => $generalPhase->id,
            'tournament_id' => $tournament->id,
        ],
        [
            'is_active' => true,
            'is_completed' => false,
        ]
    );

    $game = Game::create([
        'home_team_id' => $teams[0]->id,
        'away_team_id' => $teams[1]->id,
        'tournament_id' => $tournament->id,
        'league_id' => $tournament->league_id,
        'status' => Game::STATUS_SCHEDULED,
        'round' => 1,
        'match_date' => Carbon::now()->toDateString(),
        'match_time' => '19:00:00',
        'location_id' => $location->id,
        'home_goals' => 0,
        'away_goals' => 0,
        'tournament_phase_id' => $tournamentPhase->id,
    ]);

    return [$tournament, $location, $teams, $game];
}
