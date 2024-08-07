<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $url = 'https://ui-avatars.com/api/?name=';
        $tournaments = [
            [
                'league_id' => 1, // Asume Liga MX es ID 1
                'name' => 'Clausura 2021',
                'start_date' => '2021-01-08',
                'end_date' => '2021-05-30',
                'prize' => '1000000',
                'tournament_format_id' => 1, // Asume Torneo de Liga es ID 1
                'winner' => null,
                'description' => 'El torneo Clausura de la temporada 2021 en Liga MX.',
                'status' => 'creado',
                'category_id' => 1,
                'image' => $url . 'Clausura+2021',
            ],
            [
                'league_id' => 2, // Asume MLS es ID 2
                'name' => 'MLS Cup 2021',
                'tournament_format_id' => 2, // Asume Liga y Eliminatoria es ID 2
                'start_date' => '2021-04-17',
                'end_date' => '2021-11-07',
                'prize' => '1000000',
                'winner' => null,
                'description' => 'El torneo de copa de la temporada 2021 en MLS.',
                'status' => 'creado',
                'category_id' => 1,
                'image' => $url . 'MLS+Cup+2021',
            ]
        ];
        foreach ($tournaments as $tournament) {
            DB::table('tournaments')->insert($tournament);
        }
    }
}
