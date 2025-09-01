<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = League::all();
        foreach ($leagues as $league) {
            $league
                ->tournaments()
                ->each(function (Tournament $tournament) use ($league) {
                    $tournament->teams->each(function (Team $team) use ($league) {
                        $league->users()
                            ->whereDoesntHave('roles')
                            ->each(function (User $user) use ($team, $league) {
                                $user->assignRole('jugador');
                                Player::factory()->create([
                                    'user_id' => $user->id,
                                    'team_id' => $team->id,
                                    'position_id' => rand(1, 20),
                                    'category_id' => $team->categories()->first()->id,
                                    'nationality' => \App\Support\Fake::country(),
                                    'height' => \App\Support\Fake::numberBetween(170, 200),
                                    'weight' => \App\Support\Fake::numberBetween(70, 110),
                                    'birthdate' => \App\Support\Fake::date(),
                                    'dominant_foot' => \App\Support\Fake::randomElement(['izquierda', 'derecha']),
                                    'number' => \App\Support\Fake::numberBetween(1, 20),
                                ]);
                            });
                    });
                });
        }
    }
}
