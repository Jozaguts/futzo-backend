<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FootballType;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Tests\InitUser;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase, InitUser;

    // declare Enum of status
    public const STATUS = ['creado', 'en curso', 'completado', 'cancelado'];

    public function test_get_tournaments_with_data(): void
    {
        $this->initUser();

        $response = $this->json('GET', '/api/v1/admin/tournaments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category',
                        'format',
                        'start_date',
                        'end_date',
                        'status',
                        'name',
                        'slug',
                        'teams',
                        'players',
                        'matches',
                        'league',
                        'location',
                    ]
                ],
                'pagination'
            ]);
    }

    public function test_tournament_pagination(): void
    {
        $this->initUser();
        $response = $this->json('GET', '/api/v1/admin/tournaments?page=1&per_page=10');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category',
                        'format',
                        'start_date',
                        'end_date',
                        'status',
                        'name',
                        'slug',
                        'teams',
                        'players',
                        'matches',
                        'league',
                        'location',
                    ]
                ],
                'pagination'
            ]);
    }

    /**
     * @throws JsonException
     */
    public function test_store_tournament(): void
    {
        $this->initUser();
        Storage::fake('public');
        $image = UploadedFile::fake()->image('logo-test.jpg')->mimeType('image/jpeg');
        $formats = array_map(function (array $format) {
            return TournamentFormat::updateOrcreate(
                ['id' => $format['id']],
                $format
            );
        }, config('constants.tournament_formats'));
        $footballTypes = array_map(static function (array $type) {
            return FootballType::updateOrcreate(['id' => $type['id']], $type);
        }, config('constants.football_types')
        );
        $category = Category::factory()->create();
        $location = json_encode(config('constants.address'), JSON_THROW_ON_ERROR | true);
        $response = $this->json('POST', '/api/v1/admin/tournaments', [
            'basic' => [
                'name' => fake()->name,
                'image' => $image,
                'tournament_format_id' => $formats[0]['id'],
                'football_type_id' => $footballTypes[0]['id'],
                'category_id' => $category->first()->id,
                'start_date' => fake()->date(),
                'end_date' => fake()->date(),
                'minMax' => json_encode([18, 32], JSON_THROW_ON_ERROR | true),
            ],
            'details' => [
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

    /**
     * @throws JsonException
     */
    public function test_update_tournament_without_image(): void
    {
        $this->initUser();
        $format = TournamentFormat::find(1);
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
                'minMax' => json_encode([18, 32], JSON_THROW_ON_ERROR | true),
            ],
            'details' => [
                'start_date' => fake()->date('Y-m-d'),
                'end_date' => fake()->date('Y-m-d'),
                'prize' => fake()->text(),
                'winner' => null,
                'description' => fake()->text(),
                'status' => null,
                'location' => json_encode($location->autocomplete_prediction, JSON_THROW_ON_ERROR | true),
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

    /**
     * @throws JsonException
     */
    public function test_update_tournament_without_location(): void
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
                'minMax' => json_encode([18, 32], JSON_THROW_ON_ERROR | true),
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

    public function test_change_tournament_status(): void
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
