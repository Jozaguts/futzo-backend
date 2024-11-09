<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\InitUser;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase, InitUser;

    // declare Enum of status
    const STATUS = ['creado', 'en curso', 'completado', 'cancelado'];

    public function test_get_tournaments_with_data()
    {
        $this->initUser();

        $response = $this->json('GET', '/api/v1/admin/tournaments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    "categories" => [
                        "*" => [
                            "id",
                            "name",
                            "age_range"
                        ]
                    ],
                    "tournaments" => [
                        "*" => [
                            'id',
                            'name',
                            'teams',
                            'players',
                            'matches',
                            'league'
                        ]
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total'
                ]
            ]);
    }

    public function test_tournament_pagination()
    {
        $this->initUser();
        $response = $this->json('GET', '/api/v1/admin/tournaments?page=1&per_page=10');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    "categories" => [
                        "*" => [
                            "id",
                            "name",
                            "age_range"
                        ]
                    ],
                    "tournaments" => [
                        "*" => [
                            'id',
                            'name',
                            'teams',
                            'players',
                            'matches',
                            'league'
                        ]
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total'
                ]
            ]);
    }

    public function test_store_tournament()
    {
        $this->initUser();
        Storage::fake('public');
        $image = UploadedFile::fake()->image('logo-test.jpg')->mimeType('image/jpeg');
        $format = TournamentFormat::factory()->create(
            config('constants.tournament_formats')[0]
        );
        $category = Category::factory()->create();
        $location = json_encode(config('constants.address'), true);
        $response = $this->json('POST', '/api/v1/admin/tournaments', [
            'basic' => [
                'name' => fake()->name,
                'image' => $image,
                'tournament_format_id' => $format->first()->id,
                'category_id' => $category->first()->id,
            ],
            'details' => [
                'start_date' => fake()->date('Y-m-d'),
                'end_date' => fake()->date('Y-m-d'),
                'prize' => fake()->text(),
                'winner' => null,
                'description' => fake()->text(),
                'status' => null,
                'location' => $location,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'name',
                'tournament_format_id',
                'start_date',
                'end_date',
                'prize',
                'winner',
                'description',
                'category_id',
                'league_id',
                'id',
                'image',
                'thumbnail',
            ]);
    }

    public function test_update_tournament_without_image()
    {
        $this->initUser();
        $format = TournamentFormat::factory()->create(
            config('constants.tournament_formats')[0]
        );
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $tournament = Tournament::factory()->create();
        $tournament->format()->associate($format);
        $tournament->category()->associate($category);
        $tournament->locations()->attach($location->id);
        $tournament->save();
        $response = $this->json('PUT', '/api/v1/admin/tournaments/' . $tournament->id, [
            'basic' => [
                'name' => fake()->name,
                'tournament_format_id' => $tournament->format->id,
                'category_id' => $tournament->category->id,
            ],
            'details' => [
                'start_date' => fake()->date('Y-m-d'),
                'end_date' => fake()->date('Y-m-d'),
                'prize' => fake()->text(),
                'winner' => null,
                'description' => fake()->text(),
                'status' => null,
                'location' => json_encode($location->autocomplete_prediction, true),
            ],
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'name',
                'tournament_format_id',
                'start_date',
                'end_date',
                'prize',
                'winner',
                'description',
                'category_id',
                'id',
                'image',
                'thumbnail',
            ]);
    }

    public function test_update_tournament_without_location()
    {

        $this->initUser();
        $location = Location::factory()->create();
        $tournament = Tournament::factory()->create();
        $tournament->locations()->attach($location->id);
        $tournament->save();
        $response = $this->json('PUT', '/api/v1/admin/tournaments/' . $tournament->id, [
            'basic' => [
                'name' => fake()->name,
                'tournament_format_id' => $tournament->format->id,
                'category_id' => $tournament->category->id,
            ],
            'details' => [
                'start_date' => fake()->date('Y-m-d'),
                'end_date' => fake()->date('Y-m-d'),
                'prize' => fake()->text(),
                'winner' => null,
                'description' => fake()->text(),
                'status' => null,
            ],
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'name',
                'tournament_format_id',
                'start_date',
                'end_date',
                'prize',
                'winner',
                'description',
                'category_id',
                'id',
                'image',
                'thumbnail',
            ]);
    }

    public function test_change_tournament_status()
    {
        $this->initUser();
        $tournament = Tournament::factory()->create();
        $response = $this->json('PUT', '/api/v1/admin/tournaments/' . $tournament->id . '/status', [
            'status' => self::STATUS[1]
        ]);
        $response->assertStatus(200)
            ->assertJson([
                'status' => self::STATUS[1]
            ]);
    }
}
