<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $locations = config('constants.locations');

        foreach ($locations as $data) {
            $location = Location::updateOrCreate(
                ['id' => $data['id']],
                $data
            );
            $location->leagues()->syncWithoutDetaching([1]);
        }
    }
}
