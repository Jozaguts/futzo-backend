<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CategoriesTableSeeder;
use Database\Seeders\LeaguesTableSeeder;
use Database\Seeders\TournamentTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_tournaments_with_data()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->seed([
            CategoriesTableSeeder::class,
            LeaguesTableSeeder::class,
            TournamentTableSeeder::class,
        ]);

        $user->league_id = 1;
        $user->save();


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
}
