<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $leagues = League::with('tournaments')->get();

        foreach ($leagues as $league) {
            $league->tournaments()
                ->each(function (Tournament $tournament) use ($league) {
                    for ($i = 1; $i <= env('TEAMS_SEADER', 2); $i++) {
                        $president = User::whereDoesntHave('roles')
                            ->where('league_id', $league->id)
                            ->inRandomOrder()
                            ->first();
                        $coach = User::whereDoesntHave('roles')
                            ->where('id', '!=', $president->id)
                            ->where('league_id', $league->id)
                            ->inRandomOrder()
                            ->first();
                        $president->assignRole('dueño de equipo');
                        $coach->assignRole('entrenador');
                        $team = Team::factory()->create([
                            'coach_id' => $coach->id,
                            'president_id' => $president->id,
                        ]);

                        $league->teams()->attach($team->pluck('id'));
                        $team->tournaments()->attach($tournament->id);
                        $team->categories()->attach($tournament->category_id);
                    }

                });
        }
    }
}
