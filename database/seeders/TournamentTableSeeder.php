<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\League;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use Illuminate\Database\Seeder;

class TournamentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leagues = League::all();
        $categories = Category::all();
        $format = TournamentFormat::all();

        foreach ($leagues as $league) {
            Tournament::factory()->count(env('TOURNAMENT_SEEDER', 1))->create([
                'league_id' => $league->id,
                'category_id' => $categories->random()->id,
                'tournament_format_id' => $format->random()->id,
            ])
                ->each(function (Tournament $tournament) use ($league, $categories, $format) {
                    $tournament->locations()->attach(Location::all()->random()->id);
                });
        }

    }
}
