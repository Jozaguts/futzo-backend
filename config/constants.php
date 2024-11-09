<?php

$address = [
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
];
return [
    'address' => $address,
    'colors' => [
        'home' => [
            'jersey' => '#222',
            'short' => '#111',
        ],
        'away' => [
            'jersey' => '#dd2',
            'short' => '#dd2',
        ],
    ],
    'categories' => [
        [
            'name' => 'Amateur',
            'age_range' => '*',
            'gender' => 'male',
        ],
        [
            'name' => 'Ascenso',
            'age_range' => '*',
            'gender' => 'male',
        ],
        [
            'name' => 'Especial',
            'age_range' => '*',
            'gender' => 'male',
        ],
    ],
    'tournament_formats' => [
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
    ],
    'location' => [
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
        'autocomplete_prediction' => $address
    ]
];
