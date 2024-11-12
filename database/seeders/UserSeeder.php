<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = League::all();
        $teamPerLeague = env('TEAMS_SEADER', 2);
        $playerPerTeam = env('PLAYERS_PER_TEAM_SEEDER', 11);
        $presidents = env('PRESIDENT_TEAM_SEEDER', 1);
        $coach = env('coach_team_seeder', 1); // 11 players | 1 owner | 1 coach

        foreach ($leagues as $league) {
            User::factory()->count($playerPerTeam * ($teamPerLeague + $presidents + $coach))->create([
                'league_id' => $league->id,
            ]);
        }

    }
}
