<?php

namespace Database\Factories;

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
            'name' => 'Liga MX',
            'location' => 'Mexico',
            'football_type_id' => 1,
            'description' => 'La principal competición de fútbol profesional en México.',
            'logo' => 'https://ui-avatars.com/api/?name=Liga+MX&size=64',
            'banner' => 'https://ui-avatars.com/api/?name=Liga+MX&size=256',
            'status' => 'active',
            'creation_date' => '2021-10-10',
        ];
    }
}
