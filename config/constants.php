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
            'id' => 1,
            'name' => 'Torneo de Liga',
            'description' => 'Equipos compiten y acumulan puntos a lo largo de la temporada; el equipo con más puntos es campeón.',
            'status' => 'created',
        ],
        [
            'id' => 2,
            'name' => 'Liga y Eliminatoria',
            'description' => 'Fase de liga seguida de eliminatoria según la clasificación, ganadores avanzan hasta la final. ',
            'status' => 'created',
        ],
        [
            'id' => 3,
            'name' => 'Eliminatoria',
            'description' => 'Partidos eliminatorios desde el inicio; ganadores avanzan hasta que se determina un campeón.',
            'status' => 'created',
        ],
        [
            'id' => 4,
            'name' => 'Sistema suizo',
            'description' => 'Equipos juegan rondas contra oponentes de rendimiento similar; el equipo con más puntos gana.',
            'status' => 'created',
        ],
    ],
    'location' => $locations[0],
    'locations' => $locations,
    'football_types' => [
        [
            'id' => 1,
            'name' => 'Fútbol 11',
            'description' => 'La modalidad clásica del fútbol',
            'status' => 'created',
        ],
        [
            'id' => 2,
            'name' => 'Fútbol 7',
            'description' => 'Juego rápido en espacios reducidos.',
            'status' => 'created',
        ],
        [
            'id' => 3,
            'name' => 'Fútbol Sala',
            'description' => 'Técnica y precisión en interiores.',
            'status' => 'created',
        ],
        [
            'id' => 4,
            'name' => 'Fútbol Playa',
            'description' => 'Creatividad y esfuerzo en arena.',
            'status' => 'created',
        ],
    ],
    'default_tournament_configuration' => [
        // Fútbol 11
        [
            'tournament_format_id' => 1, // Torneo de Liga
            'football_type_id' => 1, // Fútbol 11
            'max_teams' => 20,
            'min_teams' => 8,
            'max_players_per_team' => 25,
            'min_players_per_team' => 11,
            'max_teams_per_player' => 1,
            'game_time' => 90,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        [
            'tournament_format_id' => 2, // Liga y Eliminatoria
            'football_type_id' => 1, // Fútbol 11
            'max_teams' => 16,
            'min_teams' => 8,
            'max_players_per_team' => 18,
            'min_players_per_team' => 11,
            'max_teams_per_player' => 1,
            'game_time' => 90,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => 3, // Eliminatoria
            'football_type_id' => 1, // Fútbol 11
            'max_teams' => 16,
            'min_teams' => 8,
            'max_players_per_team' => 18,
            'min_players_per_team' => 11,
            'max_teams_per_player' => 1,
            'game_time' => 90,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 0,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => 4, // Sistema Suizo
            'football_type_id' => 1, // Fútbol 11
            'max_teams' => 20,
            'min_teams' => 8,
            'max_players_per_team' => 18,
            'min_players_per_team' => 11,
            'max_teams_per_player' => 1,
            'game_time' => 90,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        // Fútbol 7
        [
            'tournament_format_id' => 1, // Torneo de Liga
            'football_type_id' => 2, // Fútbol 7
            'max_teams' => 16,
            'min_teams' => 8,
            'max_players_per_team' => 12,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        [
            'tournament_format_id' => 2, // Liga y Eliminatoria
            'football_type_id' => 2, // Fútbol 7
            'max_teams' => 12,
            'min_teams' => 6,
            'max_players_per_team' => 12,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => 3, // Eliminatoria
            'football_type_id' => 2, // Fútbol 7
            'max_teams' => 12,
            'min_teams' => 6,
            'max_players_per_team' => 12,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 0,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => 4, // Sistema Suizo
            'football_type_id' => 2, // Fútbol 7
            'max_teams' => 10,
            'min_teams' => 4,
            'max_players_per_team' => 10,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        // Fútbol Sala
        [
            'tournament_format_id' => 1, // Torneo de Liga
            'football_type_id' => 3, // Fútbol Sala
            'max_teams' => 12,
            'min_teams' => 6,
            'max_players_per_team' => 10,
            'min_players_per_team' => 5,
            'max_teams_per_player' => 1,
            'game_time' => 40,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        [
            'tournament_format_id' => 2, // Liga y Eliminatoria
            'football_type_id' => 3, // Fútbol Sala
            'max_teams' => 8,
            'min_teams' => 4,
            'max_players_per_team' => 10,
            'min_players_per_team' => 5,
            'max_teams_per_player' => 1,
            'game_time' => 40,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => 3, // Eliminatoria
            'football_type_id' => 3, // Fútbol Sala
            'max_teams' => 8,
            'min_teams' => 4,
            'max_players_per_team' => 10,
            'min_players_per_team' => 5,
            'max_teams_per_player' => 1,
            'game_time' => 40,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 0,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => 4, // Sistema Suizo
            'football_type_id' => 3, // Fútbol Sala
            'max_teams' => 10,
            'min_teams' => 4,
            'max_players_per_team' => 10,
            'min_players_per_team' => 5,
            'max_teams_per_player' => 1,
            'game_time' => 40,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        // Fútbol Playa
        [
            'tournament_format_id' => 1, // Torneo de Liga
            'football_type_id' => 4, // Fútbol Playa
            'max_teams' => 10,
            'min_teams' => 5,
            'max_players_per_team' => 12,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        [
            'tournament_format_id' => 2, // Liga y Eliminatoria
            'football_type_id' => 4, // Fútbol Playa
            'max_teams' => 8,
            'min_teams' => 4,
            'max_players_per_team' => 12,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => 3, // Eliminatoria
            'football_type_id' => 4, // Fútbol Playa
            'max_teams' => 8,
            'min_teams' => 4,
            'max_players_per_team' => 12,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 0,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => 4, // Sistema Suizo
            'football_type_id' => 4, // Fútbol Playa
            'max_teams' => 6,
            'min_teams' => 3,
            'max_players_per_team' => 10,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
    ],
    'tiebreakers' => [
        [
            'rule' => 'Puntos',
            'priority' => 1,
            'is_active' => true,
        ],
        [
            'rule' => 'Diferencia de goles',
            'priority' => 2,
            'is_active' => true,
        ],
        [
            'rule' => 'Goles a favor',
            'priority' => 3,
            'is_active' => true,
        ],
        [
            'rule' => 'Goles en contra',
            'priority' => 4,
            'is_active' => true,
        ],
        [
            'rule' => 'Resultado entre equipos',
            'priority' => 5,
            'is_active' => true,
        ],
        [
            'rule' => 'Sorteo',
            'priority' => 6,
            'is_active' => true,
        ],
    ],
    'phases' => [
        [
            'id' => 1,
            'name' => 'Tabla general',
            'is_active' => true,
            'is_completed' => false,
        ],
        [
            'id' => 2,
            'name' => 'Fase de grupos',
            'is_active' => true,
            'is_completed' => false,
        ],
        [
            'id' => 3,
            'name' => 'Octavos de Final',
            'is_active' => false,
            'is_completed' => false,
        ],
        [
            'id' => 4,
            'name' => 'Cuartos de Final',
            'is_active' => true,
            'is_completed' => false,
        ],
        [
            'id' => 5,
            'name' => 'Semifinales',
            'is_active' => true,
            'is_completed' => false,
        ],
        [
            'id' => 6,
            'name' => 'Final',
            'is_active' => true,
            'is_completed' => false,
        ],
    ],
    'availability' => [
        'monday' => [
            'enabled' => false,
            'start' => ['hours' => '09', 'minutes' => '00'],
            'end' => ['hours' => '17', 'minutes' => '00'],
        ],
        'tuesday' => [
            'enabled' => false,
            'start' => ['hours' => '09', 'minutes' => '00'],
            'end' => ['hours' => '17', 'minutes' => '00'],
        ],
        'wednesday' => [
            'enabled' => false,
            'start' => ['hours' => '09', 'minutes' => '00'],
            'end' => ['hours' => '17', 'minutes' => '00'],
        ],
        'thursday' => [
            'enabled' => false,
            'start' => ['hours' => '09', 'minutes' => '00'],
            'end' => ['hours' => '17', 'minutes' => '00'],
        ],
        'friday' => [
            'enabled' => true,
            'start' => ['hours' => '09', 'minutes' => '00'],
            'end' => ['hours' => '17', 'minutes' => '00'],
        ],
        'saturday' => [
            'enabled' => true,
            'start' => ['hours' => '09', 'minutes' => '00'],
            'end' => ['hours' => '17', 'minutes' => '00'],
        ],
        'sunday' => [
            'enabled' => true,
            'start' => ['hours' => '09', 'minutes' => '00'],
            'end' => ['hours' => '17', 'minutes' => '00'],
        ],
    ],
    'logo_path' => env('FUTZO_LOGO_PATH', 'images/text only/logo-17.png')
];
