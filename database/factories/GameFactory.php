<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Support\Fake;

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
			'date' => Fake::randomElement([now()->range(now()->startOfDay(), now()->endOfMonth())]),
			'location' => Fake::word(),
			'home_team_id' => $homeTeam->id,
			'away_team_id' => $awayTeam->id,
			'category_id' => $tournament->category->id,
			'league_id' => $league->id,
			'created_at' => now()->between(now()->startOfWeek(), now()->endOfWeek()),
			'status' => Game::STATUS_COMPLETED,
			'winner_team_id' => Fake::randomElement([$homeTeam->id, $awayTeam->id]),
			'tournament_id' => $tournament->id,
		];
	}
}
