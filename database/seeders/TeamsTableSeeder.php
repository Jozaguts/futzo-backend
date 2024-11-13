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

                        if (!$president) {
                            // Si no se encuentra un usuario para el rol de presidente, se omite este equipo
                            echo "Advertencia: No se encontró un usuario elegible para ser presidente en la liga {$league->id}.\n";
                            continue;
                        }

                        // Seleccionar al entrenador
                        $coach = User::whereDoesntHave('roles')
                            ->where('id', '!=', $president->id)
                            ->where('league_id', $league->id)
                            ->inRandomOrder()
                            ->first();

                        if (!$coach) {
                            // Si no se encuentra un usuario para el rol de entrenador, se omite este equipo
                            echo "Advertencia: No se encontró un usuario elegible para ser entrenador en la liga {$league->id}.\n";
                            continue;
                        }
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
