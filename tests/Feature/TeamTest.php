<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;


it('stores a team correctly', function () {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('logo-test.jpg')->mimeType('image/jpeg');
    $coachImage = UploadedFile::fake()->image('coach-test.jpg')->mimeType('image/jpeg');
    $category = Category::factory()->create();
    $tournament = Tournament::withoutEvents(static fn() => Tournament::factory()->create());
    $tournament->category()->associate($category);
    $tournament->save();
    $address = json_encode(config('constants.address'), JSON_THROW_ON_ERROR | true);

    $expectedColors = json_encode(config('constants.colors'), JSON_THROW_ON_ERROR | true);

    $response = $this->json('POST', '/api/v1/admin/teams', [
        'team' => [
            'name' => 'test 1',
            'address' => $address,
            'image' => $image,
            'colors' => $expectedColors,
            'category_id' => 1,
            'tournament_id' => $tournament->id,
        ],
        'president' => [
            'name' => 'John Doe',
            'phone' => fake()->phoneNumber,
            'email' => fake()->email,
        ],
        'coach' => [
            'name' => 'John Doe',
            'phone' => fake()->phoneNumber,
            'email' => fake()->email,
            'image' => $coachImage,
        ],
    ]);
    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => $response->json('coach.email'),
        'phone' => $response->json('coach.phone'),
    ]);

    $team = Team::where('name', 'test 1')->first();
    $this->assertDatabaseHas('default_lineups',[
        'team_id' => $team->id,
    ]);

});

it('stores a team with empty colors', function () {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('logo-test.jpg')->mimeType('image/jpeg');
    $category = Category::factory()->create();
    $tournament = Tournament::withoutEvents(static fn() => Tournament::factory()->create());
    $tournament->category()->associate($category);
    $tournament->save();
    $address = json_encode(config('constants.address'), JSON_THROW_ON_ERROR | true);

    $expectedColors = json_encode([], JSON_THROW_ON_ERROR);

    $response = $this->json('POST', '/api/v1/admin/teams', [
        'team' => [
            'name' => 'test 5',
            'address' => $address,
            'image' => $image,
            'colors' => $expectedColors,
            'category_id' => $tournament->category()->first()->id,
            'tournament_id' => $tournament->id,
        ],
        'president' => [],
        'coach' => [],
    ]);
    $response->assertStatus(201);

    $this->assertDatabaseHas('teams', [
        'name' => 'test 5',
    ]);
});


it('allows duplicate team names in different tournaments', function () {
    fake()->unique(true);

    $category = Category::factory()->create();

    $tournamentA = Tournament::withoutEvents(fn () => Tournament::factory()->create([
        'category_id' => $category->id,
    ]));
    $tournamentA->category()->associate($category);
    $tournamentA->save();
    $tournamentA->refresh();

    $tournamentB = Tournament::withoutEvents(fn () => Tournament::factory()->create([
        'category_id' => $category->id,
    ]));
    $tournamentB->category()->associate($category);
    $tournamentB->save();
    $tournamentB->refresh();

    $name = 'Club Futzo';

    $this->json('POST', '/api/v1/admin/teams', teamPayload($tournamentA, $name))
        ->assertCreated();

    $this->json('POST', '/api/v1/admin/teams', teamPayload($tournamentB, $name))
        ->assertCreated();
});

it('rejects duplicate team names within the same tournament', function () {
    fake()->unique(true);

    $category = Category::factory()->create();

    $tournament = Tournament::withoutEvents(fn () => Tournament::factory()->create([
        'category_id' => $category->id,
    ]));
    $tournament->category()->associate($category);
    $tournament->save();
    $tournament->refresh();

    $name = 'Club Único';

    $this->json('POST', '/api/v1/admin/teams', teamPayload($tournament, $name))
        ->assertCreated();

    $response = $this->json('POST', '/api/v1/admin/teams', teamPayload($tournament, $name));

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'El equipo ya existe en este torneo'])
        ->assertJsonFragment(['team.name' => ['El equipo ya existe en este torneo']]);
});


function teamPayload(Tournament $tournament, string $name): array
{
    $address = json_encode(config('constants.address'), JSON_THROW_ON_ERROR);
    $colors = json_encode(config('constants.colors'), JSON_THROW_ON_ERROR);
    $categoryId = optional($tournament->category()->first())->id ?? $tournament->category_id;

    return [
        'team' => [
            'name' => $name,
            'address' => $address,
            'colors' => $colors,
            'category_id' => $categoryId,
            'tournament_id' => $tournament->id,
        ],
        'president' => [],
        'coach' => [],
    ];
}
