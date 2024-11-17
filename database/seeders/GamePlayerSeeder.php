<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\League;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class GamePlayerSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = League::all();
        foreach ($leagues as $league) {
            $league->tournaments->each(function (Tournament $tournament) use ($league) {
                $tournament->games()->each(function (Game $game) use ($league) {
                    $homeTeam = $game->homeTeam;
                    $awayTeam = $game->awayTeam;
                    $homeTeam->players()->each(function (Player $player) use ($game) {
                        $game->players()->syncWithoutDetaching([
                            $player->id => [
                                'game_id' => $game->id,
                                'entry_minute' => rand(0, 45),
                                'exit_minute' => rand(45, 90),
                                'goals' => rand(0, 3),
                                'assists' => rand(0, 3),
                                'created_at' => fake()->randomElement([now()->startOfMonth(), now()->endOfMonth()]),
                            ]
                        ]);
                    });
                    $awayTeam->players()->each(function (Player $player) use ($game) {
                        $game->players()->syncWithoutDetaching([
                            $player->id => [
                                'game_id' => $game->id,
                                'entry_minute' => rand(0, 45),
                                'exit_minute' => rand(45, 90),
                                'goals' => rand(0, 3),
                                'assists' => rand(0, 3),
                                'created_at' => fake()->randomElement([now()->startOfMonth(), now()->endOfMonth()]),
                            ]
                        ]);
                    });

                });
            });
        }

    }
}
