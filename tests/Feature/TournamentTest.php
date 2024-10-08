<?php

namespace Tests\Feature;

use App\Models\Tournament;
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

        $response = $this->json('GET','/api/v1/admin/tournaments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' =>[
                    "categories" =>  [
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
        $response = $this->json('GET','/api/v1/admin/tournaments?page=1&per_page=10');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' =>[
                    "categories" =>  [
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
        $autoCompletePrediction = json_encode([
            'description' => 'La Sabana, San José Province, San José, Sabana, Costa Rica',
            'matched_substrings' => [
                [
                    'length' => 9,
                    'offset' => 0
                ]
            ],
            'place_id' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
            'reference' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
            'structured_formatting' => [
                'main_text' => 'La Sabana',
                'main_text_matched_substrings' => [
                    [
                        'length' => 9,
                        'offset' => 0
                    ]
                ],
                'secondary_text' => 'San José Province, San José, Sabana, Costa Rica'
            ],
            'terms' => [
                [
                    'offset' => 0,
                    'value' => 'La Sabana'
                ],
                [
                    'offset' => 11,
                    'value' => 'San José Province'
                ],
                [
                    'offset' => 30,
                    'value' => 'San José'
                ],
                [
                    'offset' => 40,
                    'value' => 'Sabana'
                ],
                [
                    'offset' => 48,
                    'value' => 'Costa Rica'
                ]
            ],
            'types' => [
                'establishment',
                'tourist_attraction',
                'point_of_interest',
                'park'
            ]
        ]);
        $response = $this->json('POST','/api/v1/admin/tournaments', [
            'name' => 'Tournament 1',
            'image' => $image,
            'category_id' => 1,
            'tournament_format_id' => 1,
            'start_date' => '2021-12-12',
            'end_date' => '2021-12-12',
            'prize' => 'Prize 1',
            'winner' => 'Winner 1',
            'description' => 'Tournament 1 description',
            'status' => 'creado',
            'location' => $autoCompletePrediction
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
        $autoCompletePrediction = json_encode([
            'description' => 'La Sabana, San José Province, San José, Sabana, Costa Rica',
            'matched_substrings' => [
                [
                    'length' => 9,
                    'offset' => 0
                ]
            ],
            'place_id' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
            'reference' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
            'structured_formatting' => [
                'main_text' => 'La Sabana',
                'main_text_matched_substrings' => [
                    [
                        'length' => 9,
                        'offset' => 0
                    ]
                ],
                'secondary_text' => 'San José Province, San José, Sabana, Costa Rica'
            ],
            'terms' => [
                [
                    'offset' => 0,
                    'value' => 'La Sabana'
                ],
                [
                    'offset' => 11,
                    'value' => 'San José Province'
                ],
                [
                    'offset' => 30,
                    'value' => 'San José'
                ],
                [
                    'offset' => 40,
                    'value' => 'Sabana'
                ],
                [
                    'offset' => 48,
                    'value' => 'Costa Rica'
                ]
            ],
            'types' => [
                'establishment',
                'tourist_attraction',
                'point_of_interest',
                'park'
            ]
        ]);
        $tournament = Tournament::find(1);
        $response = $this->json('PUT','/api/v1/admin/tournaments/'.$tournament->id, [
            'name' => 'Tournament 1',
            'category_id' => 1,
            'tournament_format_id' => 1,
            'start_date' => '2021-12-12',
            'end_date' => '2021-12-12',
            'prize' => 'Prize 1',
            'winner' => 'Winner 1',
            'description' => 'Tournament 1 description',
            'location' => $autoCompletePrediction
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
        $tournament = Tournament::find(1);
        $response = $this->json('PUT','/api/v1/admin/tournaments/'.$tournament->id, [
            'name' => 'Tournament 1',
            'category_id' => 1,
            'tournament_format_id' => 1,
            'start_date' => '2021-12-12',
            'end_date' => '2021-12-12',
            'prize' => 'Prize 1',
            'winner' => 'Winner 1',
            'description' => 'Tournament 1 description',
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
        $tournament = Tournament::find(1);
        $response = $this->json('PUT','/api/v1/admin/tournaments/'.$tournament->id.'/status', [
            'status' => self::STATUS[1]
        ]);
        $response->assertStatus(200)
            ->assertJson([
                'status' => self::STATUS[1]
            ]);
    }
}
