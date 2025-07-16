<?php

namespace Database\Factories;

use App\Models\DefaultLineup;
use App\Models\Formation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DefaultLineupFactory extends Factory
{
    protected $model = DefaultLineup::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'team_id' => Team::factory(),
            'formation_id' => Formation::factory(),
        ];
    }
}
