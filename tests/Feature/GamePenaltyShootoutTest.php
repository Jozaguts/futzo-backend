<?php

use App\Models\Category;
use App\Models\Country;
use App\Models\FootballType;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\League;
use App\Models\Location;
use App\Models\Penalty;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('stores penalty shootout attempts when saving goals', function () {
    [$tournament, $teams, $game] = createPenaltyShootoutReadyGame();

    $homePlayer = Player::factory()->create(['team_id' => $teams[0]->id]);
    $awayPlayer = Player::factory()->create(['team_id' => $teams[1]->id]);

    $payload = [
        'home' => [
            [
                'player_id' => $homePlayer->id,
                'minute' => 12,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
        ],
        'away' => [
            [
                'player_id' => $awayPlayer->id,
                'minute' => 54,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
        ],
        'shootout' => [
            'decided' => true,
            'home' => [
                ['player_id' => $homePlayer->id, 'score_goal' => true, 'kicks_number' => 1],
                ['player_id' => $homePlayer->id, 'score_goal' => false, 'kicks_number' => 2],
            ],
            'away' => [
                ['player_id' => $awayPlayer->id, 'score_goal' => true, 'kicks_number' => 1],
                ['player_id' => $awayPlayer->id, 'score_goal' => true, 'kicks_number' => 2],
            ],
        ],
    ];

    $this->postJson("/api/v1/admin/games/{$game->id}/goals", $payload)->assertOk();

    $game->refresh();

    expect($game->decided_by_penalties)->toBeTrue()
        ->and($game->penalty_home_goals)->toBe(1)
        ->and($game->penalty_away_goals)->toBe(2)
        ->and($game->penalty_winner_team_id)->toBe($teams[1]->id);

    $penalties = Penalty::where('game_id', $game->id)->orderBy('kicks_number')->get();
    expect($penalties)->toHaveCount(4)
        ->and($penalties->pluck('team_id')->unique()->sort()->values()->all())
        ->toEqual([$teams[0]->id, $teams[1]->id]);
});

it('rejects shootouts when the match is not tied', function () {
    [$tournament, $teams, $game] = createPenaltyShootoutReadyGame();

    $homePlayer = Player::factory()->create(['team_id' => $teams[0]->id]);
    $awayPlayer = Player::factory()->create(['team_id' => $teams[1]->id]);

    $payload = [
        'home' => [
            [
                'player_id' => $homePlayer->id,
                'minute' => 10,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
            [
                'player_id' => $homePlayer->id,
                'minute' => 18,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
        ],
        'away' => [
            [
                'player_id' => $awayPlayer->id,
                'minute' => 32,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
        ],
        'shootout' => [
            'decided' => true,
            'home' => [['player_id' => $homePlayer->id, 'score_goal' => true, 'kicks_number' => 1]],
            'away' => [['player_id' => $awayPlayer->id, 'score_goal' => false, 'kicks_number' => 1]],
        ],
    ];

    $this->postJson("/api/v1/admin/games/{$game->id}/goals", $payload)->assertStatus(422);
});

it('awards league points when finishing a draw via penalties using the game endpoints', function () {
    [$tournament, $teams, $game] = createPenaltyShootoutReadyGame();

    $homePlayer = Player::factory()->create(['team_id' => $teams[0]->id]);
    $awayPlayer = Player::factory()->create(['team_id' => $teams[1]->id]);

    $payload = [
        'home' => [
            [
                'player_id' => $homePlayer->id,
                'minute' => 12,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
        ],
        'away' => [
            [
                'player_id' => $awayPlayer->id,
                'minute' => 36,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
        ],
        'shootout' => [
            'decided' => true,
            'home' => [
                ['player_id' => $homePlayer->id, 'score_goal' => true, 'kicks_number' => 1],
                ['player_id' => $homePlayer->id, 'score_goal' => false, 'kicks_number' => 2],
            ],
            'away' => [
                ['player_id' => $awayPlayer->id, 'score_goal' => true, 'kicks_number' => 1],
                ['player_id' => $awayPlayer->id, 'score_goal' => true, 'kicks_number' => 2],
            ],
        ],
    ];

    $this->postJson("/api/v1/admin/games/{$game->id}/goals", $payload)->assertOk();

    $this->patchJson("/api/v1/admin/games/{$game->id}/complete")->assertOk();

    app(\App\Services\StandingsService::class)->recalculateStandingsForPhase(
        $tournament->id,
        $game->tournament_phase_id,
        $game->id
    );

    $standings = \App\Models\Standing::where('tournament_id', $tournament->id)->get()->keyBy('team_id');

    expect($standings[$teams[1]->id]->points)->toBe(2)
        ->and($standings[$teams[1]->id]->wins)->toBe(1)
        ->and($standings[$teams[0]->id]->points)->toBe(1)
        ->and($standings[$teams[0]->id]->losses)->toBe(1);
});

function createPenaltyShootoutReadyGame(): array
{
    $country = Country::query()->firstOrCreate(['iso_code' => 'TC'], ['name' => 'Test Country']);

    $footballTypeConfig = config('constants.football_types')[0];
    $footballType = FootballType::query()->firstOrCreate(
        ['id' => $footballTypeConfig['id']],
        $footballTypeConfig
    );

    $league = League::factory()->create([
        'country_id' => $country->id,
        'football_type_id' => $footballType->id,
    ]);

    $user = User::first();
    if ($user) {
        $user->league_id = $league->id;
        $user->saveQuietly();
        Sanctum::actingAs($user->fresh(), ['*']);
    }

    $category = Category::factory()->create();

    $formatConfig = config('constants.tournament_formats')[0];
    $tournamentFormat = TournamentFormat::query()->firstOrCreate(
        ['id' => $formatConfig['id']],
        $formatConfig
    );

    $location = Location::create([
        'name' => 'Sede ' . Str::uuid(),
        'address' => 'Dirección ' . Str::uuid(),
        'place_id' => (string) Str::uuid(),
    ]);

    $tournament = Tournament::factory()->create([
        'league_id' => $league->id,
        'category_id' => $category->id,
        'tournament_format_id' => $tournamentFormat->id,
        'football_type_id' => $footballType->id,
        'penalty_draw_enabled' => true,
    ]);

    $tournament->locations()->attach($location->id);

    attachTeamsToTournament($tournament, 2);
    $teams = $tournament->teams()->take(2)->get()->values();

    $phase = \App\Models\Phase::firstOrCreate(
        ['name' => 'Tabla general'],
        ['is_active' => true, 'is_completed' => false, 'min_teams_for' => null]
    );

    $tournamentPhase = \App\Models\TournamentPhase::firstOrCreate(
        [
            'phase_id' => $phase->id,
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
        'league_id' => $league->id,
        'status' => Game::STATUS_SCHEDULED,
        'round' => 1,
        'match_date' => now()->toDateString(),
        'match_time' => '19:00:00',
        'location_id' => $location->id,
        'home_goals' => 0,
        'away_goals' => 0,
        'tournament_phase_id' => $tournamentPhase->id,
    ]);

    return [$tournament, $teams, $game];
}

it('requires a winner in the penalty shootout', function () {
    [$tournament, $teams, $game] = createPenaltyShootoutReadyGame();

    $homePlayer = Player::factory()->create(['team_id' => $teams[0]->id]);
    $awayPlayer = Player::factory()->create(['team_id' => $teams[1]->id]);

    $payload = [
        'home' => [
            [
                'player_id' => $homePlayer->id,
                'minute' => 41,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
        ],
        'away' => [
            [
                'player_id' => $awayPlayer->id,
                'minute' => 51,
                'type' => GameEvent::GOAL,
                'related_player_id' => null,
            ],
        ],
        'shootout' => [
            'decided' => true,
            'home' => [['player_id' => $homePlayer->id, 'score_goal' => true, 'kicks_number' => 1]],
            'away' => [['player_id' => $awayPlayer->id, 'score_goal' => true, 'kicks_number' => 1]],
        ],
    ];

    $this->postJson("/api/v1/admin/games/{$game->id}/goals", $payload)->assertStatus(422);
});
