<?php

namespace Database\Factories;

use App\Models\DefaultLineup;
use App\Models\DefaultLineupPlayer;
use App\Models\Player;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DefaultLineupPlayerFactory extends Factory
{
    protected $model = DefaultLineupPlayer::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'default_lineup_id' => DefaultLineup::factory(),
            'player_id' => Player::factory(),
            'field_location' => $this->faker->numberBetween(1, 11),
        ];
    }
}
