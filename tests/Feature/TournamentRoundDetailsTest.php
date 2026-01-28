<?php

use App\Enums\TournamentFormatId;
use App\Models\Game;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

it(/**
 * @throws JsonException
 */ 'returns round details with bye team for odd team counts', function () {
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, null);
    attachTeamsToTournament($tournament, 5);

    $tournament->refresh();
    $teams = $tournament->teams()->take(5)->get()->values();
    $activePhase = $tournament->activePhase();

    $matchDate = Carbon::now()->addWeek()->toDateString();

    Game::create([
        'tournament_id' => $tournament->id,
        'league_id' => $tournament->league_id,
        'home_team_id' => $teams[0]->id,
        'away_team_id' => $teams[1]->id,
        'round' => 1,
        'match_date' => $matchDate,
        'match_time' => '19:00:00',
        'location_id' => $location->id,
        'status' => Game::STATUS_SCHEDULED,
        'tournament_phase_id' => $activePhase?->id,
    ]);

    Game::create([
        'tournament_id' => $tournament->id,
        'league_id' => $tournament->league_id,
        'home_team_id' => $teams[2]->id,
        'away_team_id' => $teams[3]->id,
        'round' => 1,
        'match_date' => $matchDate,
        'match_time' => '21:00:00',
        'location_id' => $location->id,
        'status' => Game::STATUS_SCHEDULED,
        'tournament_phase_id' => $activePhase?->id,
    ]);

    $response = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/schedule/rounds/1");

    $response
        ->assertOk()
        ->assertJsonPath('round', 1)
        ->assertJsonCount(2, 'matches')
        ->assertJsonPath('bye_team.id', $teams[4]->id);
});

it(/**
 * @throws JsonException
 */ 'accepts a fixed round schedule payload and returns 202', function () {
    Queue::fake();

    [$tournament] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, null);
    attachTeamsToTournament($tournament, 4);

    $tournament->refresh();
    $teams = $tournament->teams()->take(4)->get()->values();

    $payload = [
        'matches' => [
            ['home_team_id' => $teams[0]->id, 'away_team_id' => $teams[1]->id],
            ['home_team_id' => $teams[2]->id, 'away_team_id' => $teams[3]->id],
        ],
    ];

    $response = $this->postJson(
        "/api/v1/admin/tournaments/{$tournament->id}/schedule/rounds/1/lock",
        $payload
    );

    $response
        ->assertStatus(202)
        ->assertJsonPath('message', 'La regeneración del calendario fue enviada a la cola.');
});

it(/**
 * @throws JsonException
 */ 'accepts a forced bye payload and returns 202', function () {
    Queue::fake();

    [$tournament] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, null);
    attachTeamsToTournament($tournament, 5);

    $tournament->refresh();
    $teams = $tournament->teams()->take(5)->get()->values();

    $payload = [
        'bye_team_id' => $teams[0]->id,
    ];

    $response = $this->postJson(
        "/api/v1/admin/tournaments/{$tournament->id}/schedule/rounds/1/bye",
        $payload
    );

    $response
        ->assertStatus(202)
        ->assertJsonPath('message', 'La regeneración del calendario fue enviada a la cola.');
});
