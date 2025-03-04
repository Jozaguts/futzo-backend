<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\Location;
use Illuminate\Database\Seeder;

class FieldsTableSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::first();
        Field::factory()->count(3)->create([
            'location_id' => $location->id,
            'name' => fake()->company(),
            'type' => Field::defaultType,
            'dimensions' => Field::defaultDimensions,
        ]);
    }
}
