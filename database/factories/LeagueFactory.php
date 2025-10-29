<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\FootballType;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Support\Fake;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\League>
 */
class LeagueFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = League::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */

    public function definition()
    {

        return [
            'name' => 'Premier League',
            'description' => 'Premier League ',
            'creation_date' => Fake::dateTime(),
            'logo' => Fake::imageUrl(),
            'banner' => Fake::imageUrl(),
            'country_id' => Fake::randomElement(Country::all()->pluck('id')->toArray()),
            'football_type_id' => Fake::randomElement(FootballType::all()->pluck('id')->toArray()), // todo cambiar el football_type_id
        ];
    }
}
