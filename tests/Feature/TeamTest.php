<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\InitUser;
use Tests\TestCase;

beforeEach(function () {
    $this->user = $this->initUser();
    $this->addLeague();
});

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
            'email' => fake()->email,
            'phone' => fake()->phoneNumber,
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
            'email' => fake()->email,
            'phone' => fake()->phoneNumber,
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


