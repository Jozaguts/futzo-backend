<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\League;
use App\Models\LeagueField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LeagueFieldFactory extends Factory
{
    protected $model = LeagueField::class;

    public function definition(): array
    {
        return [
            'availability' => config('constants.availability'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'league_id' => 1,
            'field_id' => 1,
        ];
    }
}
