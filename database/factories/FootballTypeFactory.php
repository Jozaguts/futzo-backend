<?php

namespace Database\Factories;

use App\Models\FootballType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FootballTypeFactory extends Factory
{
	protected $model = FootballType::class;

	public function definition(): array
	{
		return [
			'name' => $this->faker->name(),
			'description' => $this->faker->text(),
			'status' => $this->faker->word(),
			'max_players_per_team' => $this->faker->randomNumber(),
			'min_players_per_team' => $this->faker->randomNumber(),
			'max_registered_players' => $this->faker->randomNumber(),
			'substitutions' => $this->faker->randomNumber(),
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		];
	}
}
