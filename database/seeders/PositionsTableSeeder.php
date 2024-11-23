<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $positions = [
            [
                'type' => 'portero',
                'name' => 'Portero',
                'abbr' => 'POR',
            ],
            [
                'type' => 'defensa',
                'name' => 'Defensa Central',
                'abbr' => 'DC',
            ],
            [
                'type' => 'defensa',
                'name' => 'Lateral Derecho',
                'abbr' => 'LD',
            ],
            [
                'type' => 'defensa',
                'name' => 'Lateral Izquierdo',
                'abbr' => 'LI',
            ],
            [
                'type' => 'defensa',
                'name' => 'Defensa Central Derecho',
                'abbr' => 'DCD',
            ],
            [
                'type' => 'defensa',
                'name' => 'Defensa Central Izquierdo',
                'abbr' => 'DCI',
            ],
            [
                'type' => 'defensa',
                'name' => 'Lateral Volante Derecho',
                'abbr' => 'LVD',
            ],
            [
                'type' => 'defensa',
                'name' => 'Lateral Volante Izquierdo',
                'abbr' => 'LVI',
            ],
            [
                'type' => 'medio',
                'name' => 'Mediocentro Defensivo',
                'abbr' => 'MCD',
            ],
            [
                'type' => 'medio',
                'name' => 'Mediocentro',
                'abbr' => 'MC',
            ],
            [
                'type' => 'medio',
                'name' => 'Mediocentro Ofensivo',
                'abbr' => 'MCO',
            ],
            [
                'type' => 'delantero',
                'name' => 'Extremo Derecho',
                'abbr' => 'ED',
            ],
            [
                'type' => 'delantero',
                'name' => 'Extremo Izquierdo',
                'abbr' => 'EI',
            ],
            [
                'type' => 'medio',
                'name' => 'Interior Derecho',
                'abbr' => 'ID',
            ],
            [
                'type' => 'medio',
                'name' => 'Interior Izquierdo',
                'abbr' => 'II',
            ],
            [
                'type' => 'delantero',
                'name' => 'Delantero Centro',
                'abbr' => 'DC',
            ],
            [
                'type' => 'delantero',
                'name' => 'Delantero',
                'abbr' => 'DEL',
            ],
            [
                'type' => 'delantero',
                'name' => 'Segundo Delantero',
                'abbr' => 'SD',
            ],
            [
                'type' => 'delantero',
                'name' => 'Extremo Derecho',
                'abbr' => 'ED',
            ],
            [
                'type' => 'delantero',
                'name' => 'Extremo Izquierdo',
                'abbr' => 'EI',
            ],
        ];

        foreach ($positions as $position) {
            DB::table('positions')->insert($position);
        }
    }
}
