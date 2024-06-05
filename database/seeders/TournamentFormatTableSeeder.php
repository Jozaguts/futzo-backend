<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TournamentFormatTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tournamentFormats = [
            [
                'name' => 'Torneo de Liga',
                'description' => 'Equipos compiten y acumulan puntos a lo largo de la temporada; el equipo con más puntos es campeón.',
                'status' => 'created',
            ],
            [
                'name' => 'Liga y Eliminatoria',
                'description' => 'Fase de liga seguida de eliminatoria según la clasificación; ganadores avanzan hasta la final. ',
                'status' => 'created',
            ],
            [
                'name' => 'Eliminatoria',
                'description' => 'Partidos eliminatorios desde el inicio; ganadores avanzan hasta que se determina un campeón.',
                'status' => 'created',
            ],
            [
                'name' => 'Sistema suizo',
                'description' => 'Equipos juegan rondas contra oponentes de rendimiento similar; el equipo con más puntos gana.',
                'status' => 'created',
            ],
        ];

        foreach ($tournamentFormats as $tournamentFormat) {
            \App\Models\TournamentFormat::create($tournamentFormat);
        }
    }
}
