<?php

use App\Models\Field;
use App\Models\Game;
use App\Models\League;
use App\Models\Location;
use App\Models\Team;
use App\Models\Tournament;

it('computes starts_at_utc from ISO-8601 with TZ on reschedule', function () {
    // Forzar TZ de la liga a New York para probar DST
    /** @var \App\Models\User $user */
    $user = $this->user;
    $league = League::findOrFail($user->league_id);
    $league->timezone = 'America/New_York';
    $league->save();

    // Crear locación + campo
    $this->postJson('/api/v1/admin/locations', [
        'name' => 'Sede TZ',
        'address' => 'Dir',
        'place_id' => 'tz_place_'.uniqid(),
        'position' => ['lat' => 0, 'lng' => 0],
        'fields' => [[ 'id' => 1, 'name' => 'Campo TZ', 'windows' => ['all' => [['start'=>'00:00','end'=>'24:00']]] ]],
        'fields_count' => 1,
        'steps' => ['location' => ['completed' => true], 'fields' => ['completed' => true]],
    ])->assertCreated();

    $location = Location::latest('id')->firstOrFail();
    $field = Field::where('location_id', $location->id)->firstOrFail();

    // Torneo + equipos
    [$tournament] = createTournamentViaApi(1, 1, null, $location->id);
    attachTeamsToTournament($tournament, 4);
    $teams = $tournament->teams()->take(2)->get();

    // Crear juego base
    $game = Game::create([
        'home_team_id' => $teams[0]->id,
        'away_team_id' => $teams[1]->id,
        'tournament_id' => $tournament->id,
        'league_id' => $league->id,
        'status' => Game::STATUS_SCHEDULED,
        'field_id' => $field->id,
        'location_id' => $location->id,
        'round' => 1,
        'match_date' => now()->toDateString(),
        'match_time' => '10:00:00',
    ]);

    // Reschedule con ISO-8601 incluyendo zona -05:00 (antes del salto DST en NY)
    $iso = '2025-03-09T01:30:00-05:00';
    $res = $this->putJson("/api/v1/admin/games/{$game->id}/reschedule", [
        'starts_at' => $iso,
        'field_id' => $field->id,
    ]);
    $res->assertOk();

    $game->refresh();
    $expectedUtc = \Carbon\Carbon::parse($iso)->setTimezone('UTC')->toDateTimeString();
    expect($game->starts_at_utc?->toDateTimeString())->toBe($expectedUtc);
});

it('assumes league timezone when ISO-8601 starts_at arrives without TZ', function () {
    /** @var \App\Models\User $user */
    $user = $this->user;
    $league = League::findOrFail($user->league_id);
    $league->timezone = 'America/New_York';
    $league->save();

    // Crear locación + campo
    $this->postJson('/api/v1/admin/locations', [
        'name' => 'Sede TZ2',
        'address' => 'Dir2',
        'place_id' => 'tz_place_'.uniqid(),
        'position' => ['lat' => 0, 'lng' => 0],
        'fields' => [[ 'id' => 1, 'name' => 'Campo TZ2', 'windows' => ['all' => [['start'=>'00:00','end'=>'24:00']]] ]],
        'fields_count' => 1,
        'steps' => ['location' => ['completed' => true], 'fields' => ['completed' => true]],
    ])->assertCreated();

    $location = Location::latest('id')->firstOrFail();
    $field = Field::where('location_id', $location->id)->firstOrFail();

    [$tournament] = createTournamentViaApi(1, 1, null, $location->id);
    attachTeamsToTournament($tournament, 4);
    $teams = $tournament->teams()->take(2)->get();

    $game = Game::create([
        'home_team_id' => $teams[0]->id,
        'away_team_id' => $teams[1]->id,
        'tournament_id' => $tournament->id,
        'league_id' => $league->id,
        'status' => Game::STATUS_SCHEDULED,
        'field_id' => $field->id,
        'location_id' => $location->id,
        'round' => 1,
        'match_date' => now()->toDateString(),
        'match_time' => '10:00:00',
    ]);

    // Mismo instante sin TZ explícita -> interpretar en TZ de la liga (America/New_York)
    $localIso = '2025-03-09T01:30:00';
    $expectedUtc = \Carbon\Carbon::parse($localIso, 'America/New_York')->setTimezone('UTC')->toDateTimeString();

    $res = $this->putJson("/api/v1/admin/games/{$game->id}/reschedule", [
        'starts_at' => $localIso,
        'field_id' => $field->id,
    ]);
    $res->assertOk();
    $game->refresh();
    expect($game->starts_at_utc?->toDateTimeString())->toBe($expectedUtc);
});
