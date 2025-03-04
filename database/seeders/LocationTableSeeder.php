<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::factory()->count(1)->create([
            'name' => fake()->company,
            'position' => ['lat' => fake()->latitude, 'lng' => fake()->longitude],
        ]);
    }
}
