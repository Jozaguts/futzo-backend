<?php

namespace Database\Factories;

use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Support\Fake;
use Illuminate\Support\Carbon;

class LineupPlayerFactory extends Factory
{
    protected $model = LineupPlayer::class;

    public function definition(): array
    {
        return [
            'field_location' => Fake::numberBetween(1,11),
            'substituted' => Fake::boolean(),
            'goals' => Fake::randomNumber(),
            'yellow_card' => Fake::boolean(),
            'red_card' => Fake::boolean(),
            'doble_yellow_card' => Fake::boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'lineup_id' => Lineup::factory(),
            'player_id' => Player::factory(),
        ];
    }
}
