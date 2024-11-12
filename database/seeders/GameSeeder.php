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
                $homeTeam = $tournament->teams()->inRandomOrder()->first();
                $awayTeam = $tournament->teams()->whereNot('teams.id', $homeTeam->id)->inRandomOrder()->first();
                Game::create([
                    'date' => fake()->dateTimeBetween(
                        now()->parse('first day of January ' . now()->year),
                        now()
                    ),
                    'league_id' => $league->id,
                    'tournament_id' => $tournament->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'location' => $tournament->locations()->first()->name,
                    'category_id' => $tournament->category->id,
                    'winner_team_id' => fake()->randomElement([$homeTeam->id, $awayTeam->id]),
                ]);
            });
        }

    }
}
