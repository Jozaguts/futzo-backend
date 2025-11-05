<?php

use App\Models\Category;
use App\Models\League;
use App\Models\Location;
use App\Models\Team;

it('updates home preferences when the location belongs to the league', function () {
    $league = League::firstOrFail();
    $team = Team::factory()->create();
    $team->leagues()->attach($league->id);
    $team->categories()->attach(Category::firstOrFail()->id);

    $location = Location::firstOrFail();
    $location->leagues()->syncWithoutDetaching([$league->id]);

    $payload = [
        'home_location_id' => $location->id,
        'home_day_of_week' => 4,
        'home_start_time' => '19:15',
    ];

    $response = $this->putJson("/api/v1/admin/teams/{$team->id}/home-preferences", $payload);

    $response->assertOk()
        ->assertJsonPath('home_preferences.location.id', $location->id)
        ->assertJsonPath('home_preferences.day_of_week', 4)
        ->assertJsonPath('home_preferences.start_time', '19:15');

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'home_location_id' => $location->id,
        'home_day_of_week' => 4,
        'home_start_time' => '19:15:00',
    ]);
});

it('rejects locations that are not linked to the current league', function () {
    $league = League::firstOrFail();
    $team = Team::factory()->create();
    $team->leagues()->attach($league->id);
    $team->categories()->attach(Category::firstOrFail()->id);

    $location = createStandaloneLocation();

    $response = $this->putJson("/api/v1/admin/teams/{$team->id}/home-preferences", [
        'home_location_id' => $location->id,
        'home_day_of_week' => 3,
        'home_start_time' => '18:00',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['home_location_id']);

    $this->assertDatabaseMissing('teams', [
        'id' => $team->id,
        'home_location_id' => $location->id,
    ]);
});

it('stores preferred time even without a home location', function () {
    $league = League::firstOrFail();
    $team = Team::factory()->create();
    $team->leagues()->attach($league->id);
    $team->categories()->attach(Category::firstOrFail()->id);

    $response = $this->putJson("/api/v1/admin/teams/{$team->id}/home-preferences", [
        'home_location_id' => null,
        'home_day_of_week' => null,
        'home_start_time' => '14:14',
    ]);

    $response->assertOk()
        ->assertJsonPath('home_preferences.location', null)
        ->assertJsonPath('home_preferences.day_of_week', null)
        ->assertJsonPath('home_preferences.start_time', '14:14');

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'home_location_id' => null,
        'home_day_of_week' => null,
        'home_start_time' => '14:14:00',
    ]);
});

it('allows clearing the home preferences', function () {
    $league = League::firstOrFail();
    $team = Team::factory()->create();
    $team->leagues()->attach($league->id);
    $team->categories()->attach(Category::firstOrFail()->id);

    $location = Location::firstOrFail();
    $location->leagues()->syncWithoutDetaching([$league->id]);

    $this->putJson("/api/v1/admin/teams/{$team->id}/home-preferences", [
        'home_location_id' => $location->id,
        'home_day_of_week' => 1,
        'home_start_time' => '20:30',
    ])->assertOk();

    $response = $this->putJson("/api/v1/admin/teams/{$team->id}/home-preferences", [
        'home_location_id' => null,
    ]);

    $response->assertOk()
        ->assertJsonPath('home_preferences.location', null)
        ->assertJsonPath('home_preferences.day_of_week', null)
        ->assertJsonPath('home_preferences.start_time', null);

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'home_location_id' => null,
        'home_day_of_week' => null,
        'home_start_time' => null,
    ]);
});

function createStandaloneLocation(): Location
{
    $nextId = (Location::query()->max('id') ?? 0) + 1;

    return Location::factory()->create([
        'id' => $nextId,
        'name' => 'Sede externa ' . $nextId,
        'place_id' => 'place_' . uniqid(),
    ]);
}
