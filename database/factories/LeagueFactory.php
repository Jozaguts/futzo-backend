<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\FootballType;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'name' => fake()->word(),
            'description' => fake()->text(20),
            'creation_date' => fake()->dateTime(),
            'logo' => fake()->imageUrl(),
            'banner' => fake()->imageUrl(),
            'country_id' => fake()->randomElement(Country::all()->pluck('id')->toArray()),
            'football_type_id' => fake()->randomElement(FootballType::all()->pluck('id')->toArray()),
        ];
    }
}
