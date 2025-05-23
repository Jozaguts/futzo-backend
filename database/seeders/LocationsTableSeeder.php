<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'id' => 1,
                'name' => 'Unidad Deportiva Vicente Suárez',
                'city' => 'Morelos, Zona Urbana, Acapulco de Juárez, Gro.',
                'address' => 'Unidad Deportiva Vicente Suárez, Morelos, Zona Urbana, Acapulco de Juárez, Gro.',
                'autocomplete_prediction' => 'Unidad Deportiva Vicente Suárez, Morelos, Zona Urbana, Acapulco de Juárez, Gro.',
                'position' => ['lat' => 16.8722811, 'lng' => -99.9017524],
                'created_at' => '2025-04-23 23:42:12',
                'updated_at' => '2025-05-22 23:17:05',
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'name' => 'FUTBOL 7',
                'city' => 'Genaro Padilla, El Calvario, Puerto Vallarta, Jal.',
                'address' => 'FUTBOL 7, Genaro Padilla, El Calvario, Puerto Vallarta, Jal.',
                'autocomplete_prediction' => [
                    'terms' => [],
                    'types' => [
                        'adventure_sports_center',
                        'sports_complex',
                        'establishment',
                        'point_of_interest',
                        'sports_activity_location',
                    ],
                    'place_id' => 'ChIJibeMOqZPIYQRT2NS0IdqXPY',
                    'description' => 'FUTBOL 7, Genaro Padilla, El Calvario, Puerto Vallarta, Jal.',
                    'matched_substrings' => [],
                    'structured_formatting' => [
                        'main_text' => 'FUTBOL 7',
                        'secondary_text' => 'Genaro Padilla, El Calvario, Puerto Vallarta, Jal.',
                        'main_text_matched_substrings' => [
                            ['Eg' => [null, 3]],
                        ],
                    ],
                ],
                'position' => ['lat' => 20.6615471, 'lng' => -105.217165],
                'created_at' => '2025-04-26 01:45:54',
                'updated_at' => '2025-04-26 01:45:54',
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'name' => 'Campo México 68',
                'city' => 'Calle del Río, Ciudad Renacimiento, Acapulco de Juárez, Gro.',
                'address' => 'Campo México 68, Calle del Río, Ciudad Renacimiento, Acapulco de Juárez, Gro.',
                'autocomplete_prediction' => [
                    'terms' => [],
                    'types' => ['establishment', 'playground', 'point_of_interest'],
                    'place_id' => 'ChIJ5dSZDgBXyoUR7BhXXWXZJMk',
                    'description' => 'Campo México 68, Calle del Río, Ciudad Renacimiento, Acapulco de Juárez, Gro.',
                    'matched_substrings' => [],
                    'structured_formatting' => [
                        'main_text' => 'Campo México 68',
                        'secondary_text' => 'Calle del Río, Ciudad Renacimiento, Acapulco de Juárez, Gro.',
                        'main_text_matched_substrings' => [
                            ['Eg' => [null, 15]],
                        ],
                    ],
                ],
                'position' => ['lat' => 16.883623, 'lng' => -99.8180192],
                'created_at' => '2025-05-20 23:19:01',
                'updated_at' => '2025-05-20 23:19:01',
                'deleted_at' => null,
            ],
            [
                'id' => 4,
                'name' => 'Campo de Futbol 7',
                'city' => 'Calle 1, Colonia Centro, Acapulco de Juárez, Gro.',
                'address' => 'Campo de Futbol 7, Calle 1, Colonia Centro, Acapulco de Juárez, Gro.',
                'autocomplete_prediction' => [
                    'terms' => [],
                    'types' => ['establishment', 'point_of_interest'],
                    'place_id' => 'ChIJi8g0xgBXyoUR2vXk3q5r6nE',
                    'description' => 'Campo de Futbol 7, Calle 1, Colonia Centro, Acapulco de Juárez, Gro.',
                    'matched_substrings' => [],
                    'structured_formatting' => [
                        'main_text' => 'Campo de Futbol 7',
                        'secondary_text' => 'Calle 1, Colonia Centro, Acapulco de Juárez, Gro.',
                        'main_text_matched_substrings' => [
                            ['Eg' => [null, 18]],
                        ],
                    ],
                ],
                'position' => ['lat' => 16.8531234, 'lng' => -99.823456],
                'created_at' => null,
                'updated_at' => null,
                'deleted_at' => null,
            ]
        ];

        foreach ($locations as $data) {
            $location = Location::updateOrCreate(
                ['id' => $data['id']],
                $data
            );
            $location->leagues()->syncWithoutDetaching([1]);
        }
    }
}
