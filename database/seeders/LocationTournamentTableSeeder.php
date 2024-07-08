<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationTournamentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('location_tournament')->insert([
            'tournament_id' => 1,
            'location_id' => 1,
        ]);
        DB::table('location_tournament')->insert([
            'tournament_id' => 2,
            'location_id' => 2,
        ]);

    }
}
