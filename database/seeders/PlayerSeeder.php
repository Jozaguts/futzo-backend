<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = League::all();
        foreach ($leagues as $league) {
            $league
                ->teams()
                ->each(function (Team $team) use ($league) {
                    $league
                        ->users()
                        ->whereDoesntHave('roles')
                        ->each(function (User $user) use ($team, $league) {
                            $user->assignRole('jugador');
                            Player::factory()->create([
                                'user_id' => $user->id,
                                'team_id' => $team->id,
                                'position_id' => rand(1, 20),
                                'category_id' => $team->categories()->first()->id,
                                'nationality' => fake()->country,
                                'height' => fake()->numberBetween(170, 200),
                                'weight' => fake()->numberBetween(70, 110),
                                'birthdate' => fake()->date,
                                'dominant_foot' => fake()->randomElement(['izquierda', 'derecha']),
                                'number' => fake()->numberBetween(1, 20),
                            ]);
                        });
                });
        }
    }
}
