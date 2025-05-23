<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company . ' FC',
            'address' => config('constants.address'),
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber(),
            'description' => $this->faker->sentence(10),
            'image' => '',
            'colors' => config('constants.colors'),
            'created_at' => $this->faker->randomElement([now()->startOfMonth(), now()->endOfMonth()]),
        ];
    }
}
