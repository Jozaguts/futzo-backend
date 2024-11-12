<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        $league = League::inRandomOrder()->first();
        $tournament = $league->tournaments()->inRandomOrder()->first();
        $homeTeam = $tournament->teams()->inRandomOrder()->first();
        $awayTeam = $tournament->teams()->whereNot('teams.id', $homeTeam->id)->inRandomOrder()->first();
        return [
            'date' => $this->faker->dateTimeBetween(
                now()->parse('first day of January ' . Carbon::now()->year),
                now()
            ),
            'location' => $this->faker->word(),
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'category_id' => $tournament->category->id,
            'league_id' => $league->id,
            'created_at' => now(),
            'updated_at' => now(),
            'status' => 'completado',
            'winner_team_id' => $this->faker->randomElement([$homeTeam->id, $awayTeam->id]),
            'tournament_id' => $tournament->id,
        ];
    }
}
