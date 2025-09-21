<?php

use App\Models\Game;
use App\Models\Phase;
use App\Models\Standing;
use App\Models\Tournament;
use App\Models\TournamentGroupConfiguration;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('genera un calendario para 16 equipos en liga ida y vuelta', function () {
    // Crear torneo de Liga con 16 equipos
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, null);
    attachTeamsToTournament($tournament, 16);
    $fields = $location->fields()->take(1)->get();
    $payload = [
        'general' => [
            'tournament_id' => $tournament->id,
            'tournament_format_id' => TournamentFormatId::League->value,
            'football_type_id' => 1,
            'start_date' => Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String(),
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 16,
            'locations' => [['id' => $location->id, 'name' => $location->name]],
        ],
        'rules_phase' => [
            'round_trip' => true,
            'tiebreakers' => $tournament->configuration->tiebreakers->toArray(),
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => false,
            'phases' => $tournament->tournamentPhases->load('phase')->map(function ($tournamentPhase) use ($tournament) {
                return [
                    'tournament_id' => $tournament->id,
                    'id' => $tournamentPhase->phase->id,
                    'name' => $tournamentPhase->phase->name,
                    'is_active' => $tournamentPhase->is_active,
                    'is_completed' => $tournamentPhase->is_completed,
                ];
            })->all(),
        ],
        'fields_phase' => array_map(fn($f, $i) => [
            'field_id' => $f['id'],
            'step' => $i + 1,
            'field_name' => $f['name'],
            'location_id' => $location->id,
            'location_name' => $location->name,
            'disabled' => false,
            'availability' => [
                'friday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => array_map(fn($h) => ['value' => $h, 'text' => $h, 'selected' => $i === 0, 'disabled' => false, 'in_use' => $i === 0],
                        ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00']),
                    'label' => 'Viernes',
                ],
                'saturday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => array_map(fn($h) => ['value' => $h, 'text' => $h, 'selected' => $i === 0, 'disabled' => false, 'in_use' => $i === 0],
                        ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00']),
                    'label' => 'Sábado',
                ],
                'sunday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => array_map(fn($h) => ['value' => $h, 'text' => $h, 'selected' => $i === 0, 'disabled' => false, 'in_use' => $i === 0],
                        ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00']),
                    'label' => 'Domingo',
                ],
                'isCompleted' => true,
            ],
        ], $fields->toArray(), array_keys($fields->toArray())),
    ];

    $response = $this
        ->postJson("/api/v1/admin/tournaments/{$tournament->id}/schedule", $payload);

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Calendario generado correctamente',
        ])
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'tournament_id',
                    'home_team_id',
                    'away_team_id',
                    'field_id',
                    'location_id',
                    'match_date',
                    'match_time',
                    'round',
                    'status',
                ],
            ],
        ])
        ->assertJsonPath('data.0.match_time', '09:00:00')
        ->assertJsonCount(240, 'data')
        ->assertJsonPath('data.0.field_id', $fields[0]->id);
    $this->assertDatabaseCount('games', 240);
});
it('no permite reservar horas solapadas para otro torneo', function () {
    // 1) Genera reservas para el Torneo A
    [$tournamentA, $location] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, null);
    attachTeamsToTournament($tournamentA, 8);
    $fields = $location->fields()->take(1)->get();
    $startDateString = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $payloadA = [
        'general' => [
            'tournament_id' => $tournamentA->id,
            'tournament_format_id' => TournamentFormatId::League->value,
            'football_type_id' => 1,
            'start_date' => $startDateString,
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 8,
            'locations' => [['id' => $location->id, 'name' => $location->name]],
        ],
        'rules_phase' => [
            'round_trip' => true,
            'tiebreakers' => $tournamentA->configuration->tiebreakers->toArray(),
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => false,
            'phases' => $tournamentA->tournamentPhases->load('phase')->map(function ($tournamentPhase) use ($tournamentA) {
                return [
                    'tournament_id' => $tournamentA->id,
                    'id' => $tournamentPhase->phase->id,
                    'name' => $tournamentPhase->phase->name,
                    'is_active' => $tournamentPhase->is_active,
                    'is_completed' => $tournamentPhase->is_completed,
                ];
            })->all(),
        ],
        'fields_phase' => array_map(fn($f, $i) => [
            'field_id' => $f['id'],
            'step' => $i + 1,
            'field_name' => $f['name'],
            'location_id' => $location->id,
            'location_name' => $location->name,
            'disabled' => false,
            'availability' => [
                'friday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => array_map(fn($h) => ['value' => $h, 'text' => $h, 'selected' => true, 'disabled' => false],
                        ['09:00', '11:00', '13:00', '15:00']),
                    'label' => 'Viernes',
                ],
                'isCompleted' => true,
            ],
        ], $fields->toArray(), array_keys($fields->toArray())),
    ];

    $this->postJson("/api/v1/admin/tournaments/{$tournamentA->id}/schedule", $payloadA)->assertOk();

    // 2) Intentar reservar las mismas horas para el Torneo B (misma liga)
    [$tournamentB, $locationB] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, $location->id);
    attachTeamsToTournament($tournamentB, 8);
    $payloadB = $payloadA;
    $payloadB['general']['tournament_id'] = $tournamentB->id;
    $payloadB['elimination_phase']['phases'] = $tournamentB->tournamentPhases->load('phase')->map(function ($tp) use ($tournamentB) {
        return [
            'tournament_id' => $tournamentB->id,
            'id' => $tp->phase->id,
            'name' => $tp->phase->name,
            'is_active' => $tp->is_active,
            'is_completed' => $tp->is_completed,
        ];
    })->all();

    $resConflict = $this->postJson("/api/v1/admin/tournaments/{$tournamentB->id}/schedule", $payloadB);

    // Debe fallar por superposición con reservas de otro torneo
    $resConflict->assertStatus(500);
});
it('genera fase de grupos y luego elimina con reglas por fase', function () {
    // Crear torneo Liga + Eliminatoria desde cero con 16 equipos
    [$t, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($t, 16);
    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    // 1) Generar fase de grupos (4x4, top2)
    $phases = Phase::whereIn('name', ['Fase de grupos','Dieciseisavos de Final','Octavos de Final','Cuartos de Final','Semifinales','Final'])->get()->keyBy('name');
    $payloadGroups = [
        'general' => [
            'tournament_id' => $t->id,
            'tournament_format_id' => TournamentFormatId::GroupAndElimination->value,
            'football_type_id' => 1,
            'start_date' => $startDate,
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 16,
            'round_trip' => false,
            'group_stage' => true,
            'elimination_round_trip' => true,
            'locations' => [['id' => $location->id, 'name' => $location->name]],
        ],
        'rules_phase' => [
            'round_trip' => false,
            'tiebreakers' => $t->configuration->tiebreakers->toArray(),
        ],
        'group_phase' => [
            'teams_per_group' => 4,
            'advance_top_n' => 2,
            'include_best_thirds' => false,
            'best_thirds_count' => null
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => true,
            'phases' => [
                ['id' => $phases['Fase de grupos']->id, 'name' => 'Fase de grupos', 'is_active' => true, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Octavos de Final']->id, 'name' => 'Octavos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Cuartos de Final']->id, 'name' => 'Cuartos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Semifinales']->id, 'name' => 'Semifinales', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Final']->id, 'name' => 'Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
            ],
        ],
        'fields_phase' => [[
            'field_id' => $field->id,
            'step' => 1,
            'field_name' => $field->name,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'disabled' => false,
            'availability' => [
                'friday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => [
                        ['value' => '09:00', 'text' => '09:00', 'selected' => true, 'disabled' => false],
                        ['value' => '11:00', 'text' => '11:00', 'selected' => true, 'disabled' => false],
                        ['value' => '13:00', 'text' => '13:00', 'selected' => true, 'disabled' => false],
                        ['value' => '15:00', 'text' => '15:00', 'selected' => true, 'disabled' => false]
                    ],
                    'label' => 'Viernes',
                ],
                'saturday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => [
                        ['value' => '09:00', 'text' => '09:00', 'selected' => true, 'disabled' => false],
                        ['value' => '11:00', 'text' => '11:00', 'selected' => true, 'disabled' => false],
                        ['value' => '13:00', 'text' => '13:00', 'selected' => true, 'disabled' => false],
                        ['value' => '15:00', 'text' => '15:00', 'selected' => true, 'disabled' => false]
                    ],
                    'label' => 'Sábado',
                ],
                'isCompleted' => true,
            ],
        ]],
    ];

    $resGroup = $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payloadGroups)
        ->assertOk();

    // 24 partidos esperados en grupos (4 grupos * 6 partidos)
    $resGroup->assertJsonCount(24, 'data');

    // Validar que group_key se haya asignado en pivot
    $groupCount = DB::table('team_tournament')->where('tournament_id', $t->id)->whereNotNull('group_key')->count();
    expect($groupCount)->toBeGreaterThan(0);

    // 2) Marcar los juegos de grupos como completados para que se calculen standings
    $groupPhaseId = \App\Models\TournamentPhase::where('tournament_id', $t->id)
        ->join('phases','phases.id','=','tournament_phases.phase_id')
        ->where('phases.name', 'Fase de grupos')
        ->value('tournament_phases.id');

    $games = \App\Models\Game::where('tournament_id', $t->id)
        ->where('tournament_phase_id', $groupPhaseId)
        ->get();
    foreach ($games as $g) {
        $g->update(['home_goals' => 1, 'away_goals' => 0, 'status' => \App\Models\Game::STATUS_COMPLETED]);
    }

    // 3) Activar Cuartos y generar eliminatoria (ida/vuelta)
    $payloadKO = $payloadGroups;
    // Para etapa de eliminatorias, deshabilitamos group_stage y removemos group_phase
    $payloadKO['general']['group_stage'] = false;
    $payloadKO['general']['start_date'] = Carbon::parse($startDate)->addWeeks(4)->toIso8601String();
    unset($payloadKO['group_phase']);
    $payloadKO['elimination_phase']['phases'] = [
        ['id' => $phases['Fase de grupos']->id, 'name' => 'Fase de grupos', 'is_active' => false, 'is_completed' => true, 'tournament_id' => $t->id],
        ['id' => $phases['Octavos de Final']->id, 'name' => 'Octavos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
        ['id' => $phases['Cuartos de Final']->id, 'name' => 'Cuartos de Final', 'is_active' => true, 'is_completed' => false, 'tournament_id' => $t->id,
            'rules' => ['round_trip' => true, 'away_goals' => true, 'extra_time' => true, 'penalties' => true, 'advance_if_tie' => 'better_seed']
        ],
        ['id' => $phases['Semifinales']->id, 'name' => 'Semifinales', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id,
            'rules' => ['round_trip' => true, 'away_goals' => true, 'extra_time' => true, 'penalties' => true, 'advance_if_tie' => 'better_seed']
        ],
        ['id' => $phases['Final']->id, 'name' => 'Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id,
            'rules' => ['round_trip' => false, 'away_goals' => false, 'extra_time' => true, 'penalties' => true, 'advance_if_tie' => 'none']
        ],
    ];

    $resKO = $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payloadKO)
        ->assertOk();

    // Esperados: 8 partidos (8 clasificados → 4 llaves, ida y vuelta)
    $resKO->assertJsonCount(8, 'data');
});

it('genera y expone dieciseisavos de final con 32 equipos', function () {
    [$t, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($t, 32);
    $t = $t->refresh();
    $fields = $location->fields()->take(2)->get();
    expect($fields)->toHaveCount(2);

    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    TournamentGroupConfiguration::updateOrCreate(
        ['tournament_id' => $t->id],
        [
            'teams_per_group' => 4,
            'advance_top_n' => 4,
            'include_best_thirds' => false,
            'best_thirds_count' => null,
            'group_sizes' => null,
        ]
    );

    $teamTournamentRows = DB::table('team_tournament')
        ->where('tournament_id', $t->id)
        ->orderBy('team_id')
        ->get();

    expect($teamTournamentRows)->toHaveCount(32);

    $groupPhaseId = DB::table('tournament_phases')
        ->join('phases', 'phases.id', '=', 'tournament_phases.phase_id')
        ->where('tournament_phases.tournament_id', $t->id)
        ->where('phases.name', 'Fase de grupos')
        ->value('tournament_phases.id');

    expect($groupPhaseId)->not->toBeNull();

    $primaryField = $fields->first();
    $teamsForDummyGame = $t->teams()->orderBy('teams.id')->take(2)->get();

    $dummyGame = Game::create([
        'tournament_id' => $t->id,
        'league_id' => $t->league_id,
        'home_team_id' => $teamsForDummyGame[0]->id,
        'away_team_id' => $teamsForDummyGame[1]->id,
        'field_id' => $primaryField->id,
        'location_id' => $location->id,
        'match_date' => Carbon::parse($startDate)->toDateString(),
        'match_time' => '09:00:00',
        'status' => Game::STATUS_COMPLETED,
        'round' => 1,
        'home_goals' => 1,
        'away_goals' => 0,
        'tournament_phase_id' => $groupPhaseId,
    ]);

    $letters = range('A', 'H');
    $teamTournamentRows->chunk(4)->values()->each(function ($chunk, $groupIndex) use ($letters, $groupPhaseId, $dummyGame, $t) {
        $letter = $letters[$groupIndex];
        $chunk->values()->each(function ($row, $rankIndex) use ($letter, $groupPhaseId, $dummyGame, $t, $groupIndex) {
            DB::table('team_tournament')
                ->where('id', $row->id)
                ->update(['group_key' => $letter, 'updated_at' => now()]);

            Standing::create([
                'team_id' => $row->team_id,
                'team_tournament_id' => $row->id,
                'updated_after_game_id' => $dummyGame->id,
                'tournament_phase_id' => $groupPhaseId,
                'matches_played' => 3,
                'tournament_id' => $t->id,
                'league_id' => $t->league_id,
                'wins' => max(0, 3 - $rankIndex),
                'draws' => 0,
                'losses' => $rankIndex,
                'goals_for' => 12 - $rankIndex,
                'goals_against' => $rankIndex,
                'goal_difference' => (12 - $rankIndex) - $rankIndex,
                'points' => 100 - ($groupIndex * 4 + $rankIndex),
                'fair_play_points' => 0,
                'last_5' => 'WWWWW',
                'rank' => $rankIndex + 1,
            ]);
        });
    });

    $phases = Phase::whereIn('name', ['Fase de grupos', 'Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'])
        ->get()
        ->keyBy('name');

    $intervals = ['09:00', '11:00', '13:00', '15:00'];
    $fieldsPhase = $fields->values()->map(function ($field, $index) use ($location, $intervals) {
        $mapIntervals = fn(array $hours) => array_map(static fn($hour) => [
            'value' => $hour,
            'text' => $hour,
            'selected' => true,
            'disabled' => false,
        ], $hours);

        return [
            'field_id' => $field->id,
            'step' => $index + 1,
            'field_name' => $field->name,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'disabled' => false,
            'availability' => [
                'friday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => $mapIntervals($intervals),
                    'label' => 'Viernes',
                ],
                'saturday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => $mapIntervals($intervals),
                    'label' => 'Sábado',
                ],
                'isCompleted' => true,
            ],
        ];
    })->toArray();

    $payload = [
        'general' => [
            'tournament_id' => $t->id,
            'tournament_format_id' => TournamentFormatId::GroupAndElimination->value,
            'football_type_id' => 1,
            'start_date' => Carbon::parse($startDate)->addWeeks(5)->toIso8601String(),
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 32,
            'round_trip' => false,
            'group_stage' => false,
            'elimination_round_trip' => false,
            'locations' => [['id' => $location->id, 'name' => $location->name]],
        ],
        'rules_phase' => [
            'round_trip' => false,
            'tiebreakers' => $t->configuration->tiebreakers->toArray(),
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 32,
            'elimination_round_trip' => false,
            'phases' => [
                ['id' => $phases['Fase de grupos']->id, 'name' => 'Fase de grupos', 'is_active' => false, 'is_completed' => true, 'tournament_id' => $t->id],
                ['id' => $phases['Dieciseisavos de Final']->id, 'name' => 'Dieciseisavos de Final', 'is_active' => true, 'is_completed' => false, 'tournament_id' => $t->id,
                    'rules' => ['round_trip' => false, 'away_goals' => false, 'extra_time' => true, 'penalties' => true, 'advance_if_tie' => 'better_seed']],
                ['id' => $phases['Octavos de Final']->id, 'name' => 'Octavos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Cuartos de Final']->id, 'name' => 'Cuartos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Semifinales']->id, 'name' => 'Semifinales', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Final']->id, 'name' => 'Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
            ],
        ],
        'fields_phase' => $fieldsPhase,
    ];

    $response = $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payload)
        ->assertOk();

    $response->assertJsonCount(16, 'data');

    $this->getJson("/api/v1/admin/tournaments/{$t->id}/schedule/settings")
        ->assertOk()
        ->assertJsonPath('teams_to_next_round', 32);

    $preview = $this->json('GET', "/api/v1/admin/tournaments/{$t->id}/bracket/preview?phase=Dieciseisavos de Final")
        ->assertStatus(200)
        ->json();

    expect($preview['phase'])->toBe('Dieciseisavos de Final');
    expect($preview['source'])->toBe('group_standings');
    expect($preview['target_teams'])->toBe(32);
    expect(count($preview['pairs']))->toBe(16);
    expect($preview['pairs'][0]['home_seed'])->toBe(1);
    expect($preview['pairs'][0]['away_seed'])->toBe(32);
});

it('respeta tamaños de grupo personalizados 6-6-5', function () {
    [$t, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($t, 17);
    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $phases = Phase::whereIn('name', ['Fase de grupos', 'Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'])
        ->get()
        ->keyBy('name');

    $payload = [
        'general' => [
            'tournament_id' => $t->id,
            'tournament_format_id' => TournamentFormatId::GroupAndElimination->value,
            'football_type_id' => 1,
            'start_date' => $startDate,
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 17,
            'round_trip' => false,
            'group_stage' => true,
            'elimination_round_trip' => true,
            'locations' => [['id' => $location->id, 'name' => $location->name]],
        ],
        'rules_phase' => [
            'round_trip' => false,
            'tiebreakers' => $t->configuration->tiebreakers->toArray(),
        ],
        'group_phase' => [
            'teams_per_group' => 4,
            'advance_top_n' => 2,
            'include_best_thirds' => false,
            'best_thirds_count' => null,
            'group_sizes' => [6, 6, 5],
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => true,
            'phases' => [
                ['id' => $phases['Fase de grupos']->id, 'name' => 'Fase de grupos', 'is_active' => true, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Octavos de Final']->id, 'name' => 'Octavos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Cuartos de Final']->id, 'name' => 'Cuartos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Semifinales']->id, 'name' => 'Semifinales', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Final']->id, 'name' => 'Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
            ],
        ],
        'fields_phase' => [[
            'field_id' => $field->id,
            'step' => 1,
            'field_name' => $field->name,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'disabled' => false,
            'availability' => [
                'friday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => [
                        ['value' => '09:00', 'text' => '09:00', 'selected' => true, 'disabled' => false],
                        ['value' => '11:00', 'text' => '11:00', 'selected' => true, 'disabled' => false],
                        ['value' => '13:00', 'text' => '13:00', 'selected' => true, 'disabled' => false],
                        ['value' => '15:00', 'text' => '15:00', 'selected' => true, 'disabled' => false],
                    ],
                    'label' => 'Viernes',
                ],
                'saturday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => [
                        ['value' => '09:00', 'text' => '09:00', 'selected' => true, 'disabled' => false],
                        ['value' => '11:00', 'text' => '11:00', 'selected' => true, 'disabled' => false],
                        ['value' => '13:00', 'text' => '13:00', 'selected' => true, 'disabled' => false],
                        ['value' => '15:00', 'text' => '15:00', 'selected' => true, 'disabled' => false],
                    ],
                    'label' => 'Sábado',
                ],
                'isCompleted' => true,
            ],
        ]],
    ];

    $response = $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payload)
        ->assertOk();

    $response->assertJsonCount(40, 'data');

    $groupCounts = DB::table('team_tournament')
        ->select('group_key', DB::raw('COUNT(*) as total'))
        ->where('tournament_id', $t->id)
        ->whereNotNull('group_key')
        ->groupBy('group_key')
        ->orderBy('group_key')
        ->pluck('total', 'group_key')
        ->toArray();

    expect($groupCounts)->toBe([
        'A' => 6,
        'B' => 6,
        'C' => 5,
    ]);

    $config = $t->fresh()->groupConfiguration;
    expect($config->group_sizes)->toBe([6, 6, 5]);

    $this->getJson("/api/v1/admin/tournaments/{$t->id}/schedule/settings")
        ->assertOk()
        ->assertJsonPath('group_phase.group_sizes', [6, 6, 5]);
});

it('respeta tamaños de grupo personalizados 9-8', function () {
    [$t, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($t, 17);
    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $phases = Phase::whereIn('name', ['Fase de grupos', 'Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'])
        ->get()
        ->keyBy('name');

    $payload = [
        'general' => [
            'tournament_id' => $t->id,
            'tournament_format_id' => TournamentFormatId::GroupAndElimination->value,
            'football_type_id' => 1,
            'start_date' => $startDate,
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 17,
            'round_trip' => false,
            'group_stage' => true,
            'elimination_round_trip' => true,
            'locations' => [['id' => $location->id, 'name' => $location->name]],
        ],
        'rules_phase' => [
            'round_trip' => false,
            'tiebreakers' => $t->configuration->tiebreakers->toArray(),
        ],
        'group_phase' => [
            'teams_per_group' => 4,
            'advance_top_n' => 2,
            'include_best_thirds' => false,
            'best_thirds_count' => null,
            'group_sizes' => [9, 8],
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => true,
            'phases' => [
                ['id' => $phases['Fase de grupos']->id, 'name' => 'Fase de grupos', 'is_active' => true, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Octavos de Final']->id, 'name' => 'Octavos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Cuartos de Final']->id, 'name' => 'Cuartos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Semifinales']->id, 'name' => 'Semifinales', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Final']->id, 'name' => 'Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
            ],
        ],
        'fields_phase' => [[
            'field_id' => $field->id,
            'step' => 1,
            'field_name' => $field->name,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'disabled' => false,
            'availability' => [
                'friday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => [
                        ['value' => '09:00', 'text' => '09:00', 'selected' => true, 'disabled' => false],
                        ['value' => '11:00', 'text' => '11:00', 'selected' => true, 'disabled' => false],
                        ['value' => '13:00', 'text' => '13:00', 'selected' => true, 'disabled' => false],
                        ['value' => '15:00', 'text' => '15:00', 'selected' => true, 'disabled' => false],
                    ],
                    'label' => 'Viernes',
                ],
                'saturday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => [
                        ['value' => '09:00', 'text' => '09:00', 'selected' => true, 'disabled' => false],
                        ['value' => '11:00', 'text' => '11:00', 'selected' => true, 'disabled' => false],
                        ['value' => '13:00', 'text' => '13:00', 'selected' => true, 'disabled' => false],
                        ['value' => '15:00', 'text' => '15:00', 'selected' => true, 'disabled' => false],
                    ],
                    'label' => 'Sábado',
                ],
                'isCompleted' => true,
            ],
        ]],
    ];

    $response = $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payload)
        ->assertOk();

    $response->assertJsonCount(64, 'data');

    $groupCounts = DB::table('team_tournament')
        ->select('group_key', DB::raw('COUNT(*) as total'))
        ->where('tournament_id', $t->id)
        ->whereNotNull('group_key')
        ->groupBy('group_key')
        ->orderBy('group_key')
        ->pluck('total', 'group_key')
        ->toArray();

    expect($groupCounts)->toBe([
        'A' => 9,
        'B' => 8,
    ]);

    expect($t->fresh()->groupConfiguration->group_sizes)->toBe([9, 8]);
});
