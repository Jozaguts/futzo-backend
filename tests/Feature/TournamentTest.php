<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CategoriesTableSeeder;
use Database\Seeders\LeaguesTableSeeder;
use Database\Seeders\TournamentTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\InitUser;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase, InitUser;

    public function test_get_tournaments_with_data()
    {
        $this->initUser();

        $response = $this->json('GET','/api/v1/admin/tournaments');

        $this->assertNotEmpty($response->json('categories'));
        $this->assertNotEmpty($response->json('tournaments'));
        $response->assertStatus(200)
            ->assertJsonStructure([
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
            ]);
    }

    public function test_store_tournament()
    {
        $this->initUser();

        $response = $this->json('POST','/api/v1/admin/tournaments', [
            'name' => 'Tournament 1',
            'category_id' => 1,
            'tournament_format_id' => 1,
            'start_date' => '2021-12-12',
            'end_date' => '2021-12-12',
            'prize' => 'Prize 1',
            'winner' => 'Winner 1',
            'description' => 'Tournament 1 description',
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'name',
                'category_id',
                'tournament_format_id',
                'start_date' ,
                'end_date',
                'prize',
                'winner' ,
                'description',
                'status',
            ]);
    }
}
