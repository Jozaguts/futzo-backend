<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tournament::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        return [
            'league_id' => 1, // Asume Liga MX es ID 1
            'name' => 'Clausura 2021',
            'start_date' => '2021-01-08',
            'end_date' => '2021-05-30',
            'prize' => '1000000',
            'winner' => null,
            'description' => 'El torneo Clausura de la temporada 2021 en Liga MX.',
            'status' => 'active',
            'category_id' => 1,
        ];
    }
}
