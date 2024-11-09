<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $location = config('constants.location');
        $locations = [
            [...$location, 'name' => 'Location 1'],
            [...$location, 'name' => 'Location 2'],
            [...$location, 'name' => 'Location 3']
        ];

        foreach ($locations as $location) {
            \App\Models\Location::create($location);
        }
    }
}
