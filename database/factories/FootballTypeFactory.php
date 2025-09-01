<?php

namespace Database\Factories;

use App\Models\FootballType;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Support\Fake;
use Illuminate\Support\Carbon;

class FootballTypeFactory extends Factory
{
	protected $model = FootballType::class;

	public function definition(): array
	{
		return [
			'name' => Fake::name(),
			'description' => Fake::text(),
			'status' => Fake::word(),
			'max_players_per_team' => Fake::randomNumber(),
			'min_players_per_team' => Fake::randomNumber(),
			'max_registered_players' => Fake::randomNumber(),
			'substitutions' => Fake::randomNumber(),
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		];
	}
}
