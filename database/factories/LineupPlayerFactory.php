<?php

namespace Database\Factories;

use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LineupPlayerFactory extends Factory
{
    protected $model = LineupPlayer::class;

    public function definition(): array
    {
        return [
            'field_location' => $this->faker->numberBetween(1,11),
            'substituted' => $this->faker->boolean(),
            'goals' => $this->faker->randomNumber(),
            'yellow_card' => $this->faker->boolean(),
            'red_card' => $this->faker->boolean(),
            'doble_yellow_card' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'lineup_id' => Lineup::factory(),
            'player_id' => Player::factory(),
        ];
    }
}
