<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\League;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = League::all();
        foreach ($leagues as $league) {
            $league->tournaments()->each(function (Tournament $tournament) use ($league) {
                $teams = $tournament->teams()->get();

                // Aumenta el número de juegos generando enfrentamientos entre todos los equipos
                foreach ($teams as $homeTeam) {
                    foreach ($teams->whereNotIn('id', [$homeTeam->id]) as $awayTeam) {
                        // Genera múltiples juegos por cada par de equipos
                        for ($i = 0; $i < env('GAMES_PER_PAIR', 2); $i++) {
                            Game::factory()->create([
                                'date' => fake()->dateTimeBetween('-1 month', 'now'),
                                'league_id' => $league->id,
                                'tournament_id' => $tournament->id,
                                'home_team_id' => $homeTeam->id,
                                'away_team_id' => $awayTeam->id,
                                'location' => $tournament->locations()->first()->name,
                                'category_id' => $tournament->category->id,
                                'winner_team_id' => fake()->randomElement([$homeTeam->id, $awayTeam->id, null]), // permite empates con null
                            ]);
                        }
                    }
                }
            });
        }
    }
}
