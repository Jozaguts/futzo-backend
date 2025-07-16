<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Formation;
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
    public function run(): void
    {
        Tournament::all()->each(function ($tournament) {
            $category = Category::firstOrFail();
            $league = League::firstOrFail();

          Team::factory()
                ->count(16)
                // Generamos un presidente y un coach diferentes para cada equipo
                ->state(function () use ($league) {
                    $coach = User::factory()->create(['league_id' => $league->id]);
                    $president = User::factory()->create(['league_id' => $league->id]);

                    return [
                        'coach_id' => $coach->id,
                        'president_id' => $president->id,
                    ];
                })
                // Relaciones belongsToMany
                ->hasAttached($league, [], 'leagues')
                ->hasAttached($category, [], 'categories')
                ->hasAttached($tournament, [], 'tournaments')
                ->create();
        })->firstOrFail();
        $formation = Formation::firstOrFail();
        Team::all()->each(fn ($team) => $team->defaultLineup()->create(['formation_id' => $formation->id]));
    }
}
