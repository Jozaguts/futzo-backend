<?php

enum TournamentFormatId : int
{
    case League = 1;
    case LeagueAndElimination = 2;
    case GroupAndElimination = 3;
    case Elimination = 4;
}
enum FootballTypeId: int
{
    case TraditionalFootball = 1;
    case SevenFootball = 2;
    case Futsal = 3;
}

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
        'address' => 'Unidad Deportiva Vicente Suárez, Morelos, Zona Urbana, Acapulco de Juárez, Gro.',
        'position' => ['lat' => 16.8722811, 'lng' => -99.9017524],
        'place_id' => uniqid('place_id', true)
    ],
];
return [
    'leagues_seeder' => env('LEAGUES_SEEDER',1),
    'address' => $address,
    'colors' => [
        'home' => [
            'primary' => '#fff',
            'secondary' => '#fff',
        ],
        'away' => [
            'primary' => '#fff',
            'secondary' => '#fff',
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
            'name' => 'Grupos y Eliminatoria',
            'description' => 'Fase de grupos seguida de eliminatoria; clasifican mejores por grupo (y terceros si aplica).',
            'status' => 'created',
        ],
        [
            'id' => 3,
            'name' => 'Liga y Eliminatoria',
            'description' => 'Fase de liga seguida de eliminatoria según la clasificación, ganadores avanzan hasta la final. ',
            'status' => 'created',
        ],
        [
            'id' => 4,
            'name' => 'Eliminatoria',
            'description' => 'Partidos eliminatorios desde el inicio; ganadores avanzan hasta que se determina un campeón.',
            'status' => 'created',
        ],
        [
            'id' => 5,
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
    ],
    'default_tournament_configuration' => [
        [
            'tournament_format_id' => TournamentFormatId::League,
            'football_type_id' => FootballTypeId::TraditionalFootball,
            'max_teams' => 20,
            'min_teams' => 8,
            'substitutions_per_team' => 3,
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
            'tournament_format_id' => TournamentFormatId::League,
            'football_type_id' => FootballTypeId::SevenFootball,
            'max_teams' => 16,
            'min_teams' => 8,
            'substitutions_per_team' => -1,
            'max_players_per_team' => 18,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        [
            'tournament_format_id' => TournamentFormatId::League,
            'football_type_id' => FootballTypeId::Futsal,
            'max_teams' => 16,
            'min_teams' => 8,
            'substitutions_per_team' => 5,
            'max_players_per_team' => 18,
            'min_players_per_team' => 5,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        [
            'tournament_format_id' => TournamentFormatId::LeagueAndElimination, // Eliminatoria
            'football_type_id' => FootballTypeId::TraditionalFootball, // Fútbol 11
            'max_teams' => 16,
            'min_teams' => 8,
            'substitutions_per_team' => 3,
            'max_players_per_team' => 18,
            'min_players_per_team' => 11,
            'max_teams_per_player' => 1,
            'game_time' => 90,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => TournamentFormatId::LeagueAndElimination,
            'football_type_id' => FootballTypeId::SevenFootball,
            'max_teams' => 20,
            'substitutions_per_team' => -1,
            'min_teams' => 8,
            'max_players_per_team' => 18,
            'min_players_per_team' => 11,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => TournamentFormatId::LeagueAndElimination,
            'football_type_id' => FootballTypeId::Futsal,
            'max_teams' => 16,
            'min_teams' => 8,
            'substitutions_per_team' => -1,
            'max_players_per_team' => 12,
            'min_players_per_team' => 5,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => TournamentFormatId::GroupAndElimination,
            'football_type_id' => FootballTypeId::TraditionalFootball,
            'max_teams' => 32,
            'min_teams' => 8,
            'substitutions_per_team' => 5,
            'max_players_per_team' => 23,
            'min_players_per_team' => 11,
            'max_teams_per_player' => 1,
            'game_time' => 90,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => TournamentFormatId::GroupAndElimination,
            'football_type_id' => FootballTypeId::SevenFootball, // Fútbol 7
            'max_teams' => 16,
            'min_teams' => 8,
            'substitutions_per_team' => -1,
            'max_players_per_team' => 18,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => TournamentFormatId::GroupAndElimination,
            'football_type_id' => FootballTypeId::Futsal,
            'max_teams' => 16,
            'min_teams' => 8,
            'substitutions_per_team' => -1,
            'max_players_per_team' => 15,
            'min_players_per_team' => 5,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 1,
            'group_stage' => 1,
            'elimination_round_trip' => 1
        ],
        [
            'tournament_format_id' => TournamentFormatId::Elimination,
            'football_type_id' => FootballTypeId::TraditionalFootball,
            'max_teams' => 32,
            'min_teams' => 8,
            'substitutions_per_team' => 5,
            'max_players_per_team' => 23,
            'min_players_per_team' => 11,
            'max_teams_per_player' => 1,
            'game_time' => 90,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        [
            'tournament_format_id' => TournamentFormatId::Elimination,
            'football_type_id' => FootballTypeId::SevenFootball,
            'max_teams' => 32,
            'min_teams' => 8,
            'substitutions_per_team' => -1,
            'max_players_per_team' => 18,
            'min_players_per_team' => 7,
            'max_teams_per_player' => 1,
            'game_time' => 60,
            'time_between_games' => 0,
            'round_trip' => 0,
            'group_stage' => 0,
            'elimination_round_trip' => 0
        ],
        [
            'tournament_format_id' => TournamentFormatId::Elimination,
            'football_type_id' => FootballTypeId::Futsal,
            'max_teams' => 32,
            'min_teams' => 8,
            'substitutions_per_team' => -1,
            'max_players_per_team' => 18,
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
            'min_teams_for' => null,
        ],
        [
            'id' => 2,
            'name' => 'Fase de grupos',
            'is_active' => true,
            'is_completed' => false,
            'min_teams_for' => null,
        ],
        [
            'id' => 3,
            'name' => 'Octavos de Final',
            'is_active' => false,
            'is_completed' => false,
            'min_teams_for' => 16,
        ],
        [
            'id' => 4,
            'name' => 'Cuartos de Final',
            'is_active' => true,
            'is_completed' => false,
            'min_teams_for' => 8
        ],
        [
            'id' => 5,
            'name' => 'Semifinales',
            'is_active' => true,
            'is_completed' => false,
            'min_teams_for' => 4
        ],
        [
            'id' => 6,
            'name' => 'Final',
            'is_active' => true,
            'is_completed' => false,
            'min_teams_for' => 2
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
    'logo_path' => env('FUTZO_LOGO_PATH', 'images/text only/logo-17.png'),
    'label_days' => [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ],
    'nationalities' => [
        "Mexicano",
        "Argentino",
        "Brasileño",
        "Uruguayo",
        "Chileno",
        "Colombiano",
        "Paraguayo",
        "Ecuatoriano",
        "Peruano",
        "Boliviano",
        "Venezolano",
        "Costarricense",
        "Hondureño",
        "Salvadoreño",
        "Guatemalteco",
        "Panameño",
        "Canadiense",
        "Estadounidense",
        "Español",
        "Portugués",
        "Francés",
        "Italiano",
        "Alemán",
        "Holandés",
        "Inglés",
        "Otro",
    ]
];
