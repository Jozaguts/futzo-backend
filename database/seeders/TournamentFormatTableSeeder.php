<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TournamentFormatTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tournamentFormats = config('constants.tournament_formats');

        foreach ($tournamentFormats as $tournamentFormat) {
            \App\Models\TournamentFormat::create($tournamentFormat);
        }
    }
}
