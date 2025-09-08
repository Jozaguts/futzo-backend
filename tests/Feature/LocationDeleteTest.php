<?php

use App\Models\Field;
use App\Models\Game;
use App\Models\Location;
use App\Models\Team;
use App\Models\Tournament;

it('blocks deleting a location with scheduled games on its fields', function () {
    // Crear locación con un campo vía API (asegura pivot con la liga del usuario)
    $storeResponse = $this->postJson('/api/v1/admin/locations', [
        'name' => 'Sede Programada',
        'address' => 'Dirección de prueba',
        'place_id' => 'test_place_'.uniqid(),
        'position' => ['lat' => 20.0, 'lng' => -103.0],
        'tags' => ['test'],
        'fields' => [[
            'id' => 1,
            'name' => 'Campo A',
            'windows' => [
                'mon' => [], 'tue' => [], 'wed' => [], 'thu' => [], 'fri' => [], 'sat' => [], 'sun' => [], 'all' => []
            ],
        ]],
        'fields_count' => 1,
        'steps' => [
            'location' => ['completed' => true],
            'fields' => ['completed' => true],
        ],
    ]);
    $storeResponse->assertCreated();

    $location = Location::latest('id')->firstOrFail();
    $field = Field::where('location_id', $location->id)->firstOrFail();

    // Crear torneo vinculado a la locación y equipos
    [$tournament] = createTournamentViaApi(1, 1, null, $location->id);
    attachTeamsToTournament($tournament, 4);
    $teams = $tournament->teams()->take(2)->get();
    [$home, $away] = [$teams[0], $teams[1]];

    // Crear juego programado en el campo de la locación
    Game::create([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'tournament_id' => $tournament->id,
        'league_id' => $tournament->league_id,
        'status' => Game::STATUS_SCHEDULED,
        'field_id' => $field->id,
        'location_id' => $location->id,
        'round' => 1,
        'match_date' => now()->addDays(2)->toDateString(),
        'match_time' => '18:00:00',
    ]);

    // Intentar eliminar: debe bloquear con 422
    $resp = $this->deleteJson('/api/v1/admin/locations/'.$location->id);
    $resp->assertStatus(422)
        ->assertJson(['message' => 'No puedes eliminar esta locación porque tiene partidos programados o en progreso.']);

    // Asegurar que NO se haya hecho soft delete
    $this->assertDatabaseHas('locations', [
        'id' => $location->id,
        'deleted_at' => null,
    ]);
});

it('allows soft-deleting a location when games are completed only', function () {
    // Crear locación con campo
    $storeResponse = $this->postJson('/api/v1/admin/locations', [
        'name' => 'Sede Completada',
        'address' => 'Dirección de prueba 2',
        'place_id' => 'test_place_'.uniqid(),
        'position' => ['lat' => 21.0, 'lng' => -102.0],
        'tags' => ['ok'],
        'fields' => [[
            'id' => 1,
            'name' => 'Campo B',
            'windows' => [
                'mon' => [], 'tue' => [], 'wed' => [], 'thu' => [], 'fri' => [], 'sat' => [], 'sun' => [], 'all' => []
            ],
        ]],
        'fields_count' => 1,
        'steps' => [
            'location' => ['completed' => true],
            'fields' => ['completed' => true],
        ],
    ]);
    $storeResponse->assertCreated();

    $location = Location::latest('id')->firstOrFail();
    $field = Field::where('location_id', $location->id)->firstOrFail();

    // Crear torneo y equipos
    [$tournament] = createTournamentViaApi(1, 1, null, $location->id);
    attachTeamsToTournament($tournament, 4);
    $teams = $tournament->teams()->take(2)->get();
    [$home, $away] = [$teams[0], $teams[1]];

    // Crear juego COMPLETADO en la locación
    Game::create([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'tournament_id' => $tournament->id,
        'league_id' => $tournament->league_id,
        'status' => Game::STATUS_COMPLETED,
        'field_id' => $field->id,
        'location_id' => $location->id,
        'round' => 1,
        'match_date' => now()->subDays(1)->toDateString(),
        'match_time' => '10:00:00',
    ]);

    // Eliminar: debe permitir soft delete
    $resp = $this->deleteJson('/api/v1/admin/locations/'.$location->id);
    $resp->assertOk();

    // Verificar soft delete
    $this->assertSoftDeleted('locations', ['id' => $location->id]);
});

