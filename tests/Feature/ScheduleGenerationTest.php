<?php

use App\Models\Phase;
use App\Models\Tournament;
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
        'regular_phase' => [
            'round_trip' => true,
            'tiebreakers' => $tournament->configuration->tiebreakers->toArray(),
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'round_trip' => false,
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
        'regular_phase' => [
            'round_trip' => true,
            'tiebreakers' => $tournamentA->configuration->tiebreakers->toArray(),
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'round_trip' => false,
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
    $phases = Phase::whereIn('name', ['Fase de grupos','Octavos de Final','Cuartos de Final','Semifinales','Final'])->get()->keyBy('name');
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
        'regular_phase' => [
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
            'round_trip' => true,
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
