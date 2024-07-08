<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $autoCompletePredictions =  [
            [
                'description' => 'La Sabana, San José Province, San José, Sabana, Costa Rica',
                'matched_substrings' => [
                    [
                        'length' => 9,
                        'offset' => 0
                    ]
                ],
                'place_id' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
                'reference' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
                'structured_formatting' => [
                    'main_text' => 'La Sabana',
                    'main_text_matched_substrings' => [
                        [
                            'length' => 9,
                            'offset' => 0
                        ]
                    ],
                    'secondary_text' => 'San José Province, San José, Sabana, Costa Rica'
                ],
                'terms' => [
                    [
                        'offset' => 0,
                        'value' => 'La Sabana'
                    ],
                    [
                        'offset' => 11,
                        'value' => 'San José Province'
                    ],
                    [
                        'offset' => 30,
                        'value' => 'San José'
                    ],
                    [
                        'offset' => 40,
                        'value' => 'Sabana'
                    ],
                    [
                        'offset' => 48,
                        'value' => 'Costa Rica'
                    ]
                ],
                'types' => [
                    'establishment',
                    'tourist_attraction',
                    'point_of_interest',
                    'park'
                ]
            ],

            // generate another one
            [
                'description' => 'La Sabana, San José Province, San José, Sabana, Costa Rica',
                'matched_substrings' => [
                    [
                        'length' => 9,
                        'offset' => 0
                    ]
                ],
                'place_id' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
                'reference' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
                'structured_formatting' => [
                    'main_text' => 'La Sabana',
                    'main_text_matched_substrings' => [
                        [
                            'length' => 9,
                            'offset' => 0
                        ]
                    ],
                    'secondary_text' => 'San José Province, San José, Sabana, Costa Rica'
                ],
                'terms' => [
                    [
                        'offset' => 0,
                        'value' => 'La Sabana'
                    ],
                    [
                        'offset' => 11,
                        'value' => 'San José Province'
                    ],
                    [
                        'offset' => 30,
                        'value' => 'San José'
                    ],
                    [
                        'offset' => 40,
                        'value' => 'Sabana'
                    ],
                    [
                        'offset' => 48,
                        'value' => 'Costa Rica'
                    ]
                ],
                'types' => [
                    'establishment',
                    'tourist_attraction',
                    'point_of_interest',
                    'park'
                ]
            ]
        ];
        $locations = [
            [
                'name' => 'Location 1',
                'city' => 'City 1',
                'address' => 'Address 1',
                'availability' => [
                        'monday' => ['start' => '08:00', 'end' => '20:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                        'thursday' => ['start' => '08:00', 'end' => '20:00'],
                        'friday' => ['start' => '08:00', 'end' => '20:00'],
                        'saturday' => ['start' => '08:00', 'end' => '20:00'],
                        'sunday' => ['start' => '08:00', 'end' => '20:00'],
                    ],
                'autocomplete_prediction' => $autoCompletePredictions[0]
            ],
            [
                'name' => 'Location 2',
                'city' => 'City 2',
                'address' => 'Address 2',
                'availability' => [
                        'monday' => ['start' => '08:00', 'end' => '20:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                        'thursday' => ['start' => '08:00', 'end' => '20:00'],
                        'friday' => ['start' => '08:00', 'end' => '20:00'],
                        'saturday' => ['start' => '08:00', 'end' => '20:00'],
                        'sunday' => ['start' => '08:00', 'end' => '20:00'],
                    ],
                'autocomplete_prediction' => $autoCompletePredictions[1]
            ],
            [
                'name' => 'Location 3',
                'city' => 'City 3',
                'address' => 'Address 3',
                'availability' => [
                        'monday' => ['start' => '08:00', 'end' => '20:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                        'thursday' => ['start' => '08:00', 'end' => '20:00'],
                        'friday' => ['start' => '08:00', 'end' => '20:00'],
                        'saturday' => ['start' => '08:00', 'end' => '20:00'],
                        'sunday' => ['start' => '08:00', 'end' => '20:00'],
                    ],
                'autocomplete_prediction' => $autoCompletePredictions
            ]

        ];

        foreach ($locations as $location) {
            \App\Models\Location::create($location);
        }
    }
}
