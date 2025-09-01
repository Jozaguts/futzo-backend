<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Support\Fake;

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
            'name' => $name = Fake::domainWord() . ' FC',
            'address' => config('constants.address'),
            'email' => Fake::safeEmail(),
            'phone' => Fake::phoneNumber(),
            'description' => Fake::sentence(10),
            'image' => 'https://ui-avatars.com/api/?name=' . $name,
            'colors' => config('constants.colors'),
            'created_at' => Fake::randomElement([now()->startOfMonth(), now()->endOfMonth()]),
        ];
    }
}
