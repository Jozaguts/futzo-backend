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
        $teamPerLeague = 5;
        $playerPerTeam = 13; // 11 players | 1 owner | 1 coach

        foreach ($leagues as $league) {
            User::factory()->count($playerPerTeam * $teamPerLeague)->create([
                'league_id' => $league->id,
            ]);
        }

    }
}
