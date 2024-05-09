<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FootballTypesTableSedder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $footballTypes = [
            [
                'name' => 'Fútbol 11',
                'description' => 'El fútbol 11 es la modalidad más común de fútbol en todo el mundo. Se juega con dos equipos de 11 jugadores cada uno, un balón y dos porterías. El objetivo es marcar más goles que el equipo contrario para ganar el partido.',
                'status' => 'created',
            ],
            [
                'name' => 'Fútbol 7',
                'description' => 'El fútbol 7 es una variante del fútbol 11 que se juega con dos equipos de 7 jugadores cada uno. Es una modalidad más rápida y dinámica que el fútbol 11, ideal para espacios reducidos y equipos más pequeños.',
                'status' => 'created',
            ],
            [
                'name' => 'Fútbol Sala',
                'description' => 'El fútbol sala es una variante del fútbol que se juega en un campo más pequeño y con reglas ligeramente diferentes. Se juega con dos equipos de 5 jugadores cada uno, un balón y dos porterías. Es un deporte rápido y emocionante que requiere habilidad y destreza.',
                'status' => 'created',
            ],
            [
                'name' => 'Fútbol Playa',
                'description' => 'El fútbol playa es una variante del fútbol que se juega en la arena, con dos equipos de 5 jugadores cada uno. Es un deporte espectacular y emocionante, que combina habilidad, destreza y espectáculo en un entorno único.',
                'status' => 'created',
            ],
        ];

        foreach ($footballTypes as $footballType) {
            \App\Models\FootballType::create($footballType);
        }
    }
}
