<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TieBreakersTableSeeder extends Seeder
{
    public function run(): void
    {
        $tieBreakers = config('constants.tiebreakers');
        $tournamentConfigurations = \App\Models\TournamentConfiguration::all();
        foreach ($tournamentConfigurations as $tournamentConfiguration) {
            foreach ($tieBreakers as $tieBreaker) {
                $tieBreaker['tournament_configuration_id'] = $tournamentConfiguration->id;
                \App\Models\TournamentTiebreaker::create($tieBreaker);
            }
        }
    }
}
