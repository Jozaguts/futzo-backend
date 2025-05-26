<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $league = League::firstOrFail();
        foreach (config('constants.locations') as $data) {
            $location = Location::updateOrCreate(
                ['id' => $data['id']],
                $data
            );
            $league?->locations()->attach($location->id, ['updated_at' => now(), 'created_at' => now()]);
        }
    }
}
