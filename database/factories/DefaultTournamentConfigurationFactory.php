<?php

namespace Database\Factories;

use App\Models\DefaultTournamentConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DefaultTournamentConfiguration> */
class DefaultTournamentConfigurationFactory extends Factory
{
    protected $model = DefaultTournamentConfiguration::class;

    public function definition(): array
    {
        return config('constants.default_tournament_configuration')[0];
    }
}
