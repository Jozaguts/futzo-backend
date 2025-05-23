<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\Tournament;
use App\Models\TournamentField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TournamentFieldFactory extends Factory
{
    protected $model = TournamentField::class;

    public function definition(): array
    {
        return [
            'availability' => $this->faker->words(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'tournament_id' => Tournament::factory(),
            'field_id' => Field::factory(),
        ];
    }
}
