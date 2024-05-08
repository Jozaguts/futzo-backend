<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TournamentTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tournamentTypes = [
            [
                'name' => 'Torneo de Liga',
                'description' => 'En este torneo, conocido también como sistema Round Robin o todos contra todos, cada equipo compite con los demás y acumula puntos a lo largo de la temporada. El equipo con más puntos al final de todos los encuentros se proclama campeón.',
                'status' => 'created',
            ],
            [
                'name' => 'Liga y Eliminatoria',
                'description' => 'Este formato combina una fase de liga, donde todos los equipos se enfrentan entre sí, con una fase eliminatoria que comienza al terminar la liga. En la eliminatoria, los equipos se enfrentan en base a su clasificación final en la liga; el primero contra el último, el segundo contra el penúltimo, y así sucesivamente, avanzando los ganadores hasta determinar al campeón en un partido final. ',
                'status' => 'created',
            ],
            [
                'name' => 'Eliminatoria',
                'description' => 'En este tipo de torneo, los equipos se enfrentan en una serie de partidos eliminatorios desde el principio, avanzando únicamente los ganadores de cada ronda hasta que se determina un campeón final. Este formato es común en torneos cortos o de un solo día.',
                'status' => 'created',
            ],
            [
                'name' => 'Swiss System',
                'description' => 'En el sistema suizo, los equipos juegan un número determinado de rondas contra oponentes que tienen un rendimiento similar durante el torneo, lo cual permite que cada equipo tenga la oportunidad de competir en condiciones más equitativas. Al final de las rondas, el equipo con más puntos acumulados se proclama campeón.',
                'status' => 'created',
            ],
        ];

        foreach ($tournamentTypes as $tournamentType) {
            \App\Models\TournamentType::create($tournamentType);
        }
    }
}
