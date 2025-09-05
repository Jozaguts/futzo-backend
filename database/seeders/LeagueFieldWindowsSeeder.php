<?php

namespace Database\Seeders;

use App\Models\LeagueField;
use App\Models\LeagueFieldWindow;
use Illuminate\Database\Seeder;

class LeagueFieldWindowsSeeder extends Seeder
{
    public function run(): void
    {
        // Por cada league_field, si no tiene ventanas, crear 7 filas 24/7
        LeagueField::query()->chunkById(200, function ($items) {
            foreach ($items as $lf) {
                if (LeagueFieldWindow::where('league_field_id', $lf->id)->exists()) {
                    continue;
                }
                for ($dow = 0; $dow <= 6; $dow++) {
                    LeagueFieldWindow::create([
                        'league_field_id' => $lf->id,
                        'day_of_week' => $dow,
                        'start_minute' => 0,
                        'end_minute' => 1440,
                        'enabled' => true,
                    ]);
                }
            }
        });
    }
}

