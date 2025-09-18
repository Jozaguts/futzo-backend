<?php

use App\Models\Game;
use App\Models\Phase;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('exposes group standings and bracket preview, and confirms bracket scheduling', function () {
    // 1) Crear torneo formato Grupos y Eliminatoria y 16 equipos
    [$t, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($t, 8);
    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    // 2) Generar Fase de Grupos (4x4, top2)
    $phases = Phase::whereIn('name', ['Fase de grupos','Octavos de Final','Cuartos de Final','Semifinales','Final'])->get()->keyBy('name');
    $payloadGroups = [
        'general' => [
            'tournament_id' => $t->id,
            'tournament_format_id' => TournamentFormatId::GroupAndElimination->value,
            'football_type_id' => 1,
            'start_date' => $startDate,
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 8,
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
    $resGroup = $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payloadGroups)->assertOk();
    // 2 grupos de 4 -> 6 partidos por grupo => 12
    $resGroup->assertJsonCount(12, 'data');

    // 3) Completar los juegos de grupos para generar standings
    $groupPhaseId = \App\Models\TournamentPhase::where('tournament_id', $t->id)
        ->join('phases','phases.id','=','tournament_phases.phase_id')
        ->where('phases.name', 'Fase de grupos')
        ->value('tournament_phases.id');
    $games = Game::where('tournament_id', $t->id)->where('tournament_phase_id', $groupPhaseId)->get();
    foreach ($games as $g) {
        $g->update(['home_goals' => 1, 'away_goals' => 0, 'status' => Game::STATUS_COMPLETED]);
    }

    // 4) Standings por grupo
    $standings = $this->json('GET', "/api/v1/admin/tournaments/{$t->id}/group-standings")
        ->assertStatus(200)
        ->json();
    expect($standings['tournament_id'])->toBe($t->id);
    expect($standings['groups'])->not->toBeEmpty();
    expect($standings['groups'][0]['teams'][0])->toHaveKeys(['team_id','team_name','rank','points']);

    // 5) Bracket preview (Semifinales) — 8 equipos → 4 clasificados
    $preview = $this->json('GET', "/api/v1/admin/tournaments/{$t->id}/bracket/preview?phase=Semifinales")
        ->assertStatus(200)
        ->json();
    expect($preview)->toHaveKeys(['phase','target_teams','source','qualifiers','pairs']);
    expect($preview['phase'])->toBe('Semifinales');
    expect(count($preview['pairs']))->toBe(2); // 4 equipos → 2 cruces

    // 6) Activar fase de Semifinales
    $tpSemisId = \App\Models\TournamentPhase::where('tournament_id', $t->id)
        ->join('phases','phases.id','=','tournament_phases.phase_id')
        ->where('phases.name', 'Semifinales')
        ->value('tournament_phases.id');
    $this->json('PUT', "/api/v1/admin/tournaments/{$t->id}/phases/{$tpSemisId}", [
        'is_active' => true,
        'is_completed' => false,
    ])->assertStatus(200);

    // 7) Confirmar bracket: programar 2 partidos (ida) en una fecha sin conflicto
    $confirmDate = Carbon::parse($startDate)->addWeeks(4)->toDateString();
    $times = ['09:00','11:00'];
    $matches = [];
    foreach ($preview['pairs'] as $i => $pair) {
        $matches[] = [
            'home_team_id' => $pair['home']['team_id'],
            'away_team_id' => $pair['away']['team_id'],
            'field_id' => $field->id,
            'match_date' => $confirmDate,
            'match_time' => $times[$i],
            'leg' => 1,
        ];
    }
    $resConfirm = $this->postJson("/api/v1/admin/tournaments/{$t->id}/bracket/confirm", [
        'phase' => 'Semifinales',
        'min_rest_minutes' => 120,
        'matches' => $matches,
    ])->assertStatus(200);
    $resConfirm->assertJsonStructure(['message','phase','data' => [['id','home_team_id','away_team_id','match_date','match_time','field_id','round']]])
        ->assertJsonCount(2, 'data');
});

it('rejects bracket confirm when rest time or field-time duplicates are invalid', function () {
    [$t, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($t, 8);
    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    // Generar grupos mínimos (solo para tener equipos y contexto)
    $phases = Phase::whereIn('name', ['Fase de grupos','Octavos de Final','Cuartos de Final','Semifinales','Final'])->get()->keyBy('name');
    $payloadGroups = [
        'general' => [
            'tournament_id' => $t->id,
            'tournament_format_id' => TournamentFormatId::GroupAndElimination->value,
            'football_type_id' => 1,
            'start_date' => $startDate,
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 8,
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
                    ],
                    'label' => 'Viernes',
                ],
                'saturday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => [
                        ['value' => '09:00', 'text' => '09:00', 'selected' => true, 'disabled' => false],
                        ['value' => '11:00', 'text' => '11:00', 'selected' => true, 'disabled' => false],
                    ],
                    'label' => 'Sábado',
                ],
                'isCompleted' => true,
            ],
        ]],
    ];
    $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payloadGroups)->assertOk();

    // Activar Cuartos
    $tpCuartosId = \App\Models\TournamentPhase::where('tournament_id', $t->id)
        ->join('phases','phases.id','=','tournament_phases.phase_id')
        ->where('phases.name', 'Cuartos de Final')
        ->value('tournament_phases.id');
    $this->json('PUT', "/api/v1/admin/tournaments/{$t->id}/phases/{$tpCuartosId}", [
        'is_active' => true,
        'is_completed' => false,
    ])->assertStatus(200);

    // Intentar confirmar bracket con violación de descanso (mismo equipo dos veces con poco tiempo entre partidos)
    $teams = $t->teams()->pluck('teams.id')->toArray();
    $confirmDate = Carbon::parse($startDate)->addWeeks(4)->toDateString();
    $badMatches = [
        [ 'home_team_id' => $teams[0], 'away_team_id' => $teams[1], 'field_id' => $field->id, 'match_date' => $confirmDate, 'match_time' => '09:00', 'leg' => 1 ],
        [ 'home_team_id' => $teams[0], 'away_team_id' => $teams[2], 'field_id' => $field->id, 'match_date' => $confirmDate, 'match_time' => '10:00', 'leg' => 1 ],
    ];
    $this->postJson("/api/v1/admin/tournaments/{$t->id}/bracket/confirm", [
        'phase' => 'Cuartos de Final',
        'min_rest_minutes' => 120,
        'matches' => $badMatches,
    ])->assertStatus(422);

    // Intentar confirmar bracket con duplicado campo/hora
    $dupMatches = [
        [ 'home_team_id' => $teams[3], 'away_team_id' => $teams[4], 'field_id' => $field->id, 'match_date' => $confirmDate, 'match_time' => '11:00', 'leg' => 1 ],
        [ 'home_team_id' => $teams[5], 'away_team_id' => $teams[6], 'field_id' => $field->id, 'match_date' => $confirmDate, 'match_time' => '11:00', 'leg' => 1 ],
    ];
    $this->postJson("/api/v1/admin/tournaments/{$t->id}/bracket/confirm", [
        'phase' => 'Cuartos de Final',
        'matches' => $dupMatches,
    ])->assertStatus(422);
});
