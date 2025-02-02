<?php

namespace Database\Factories;

use App\Models\TournamentTiebreaker;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentTiebreakerFactory extends Factory
{
    protected $model = TournamentTiebreaker::class;

    public function definition(): array
    {
        $default = config('constants.tiebreakers');
        return [
            'rule' => $default[0]['rule'],
            'priority' => $default[0]['priority'],
            'is_active' => true,
            'tournament_configuration_id' => 1,
        ];
    }
}
