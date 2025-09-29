<?php

use App\Enums\TournamentFormatId;
use App\Models\Game;
use App\Models\Phase;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

it('previews and confirms bracket using table standings for Liga + Eliminatoria', function () {
    // 1) Torneo Liga + Eliminatoria con 8 equipos
    [$t, $location] = createTournamentViaApi(TournamentFormatId::LeagueAndElimination->value, 1, null, null);
    $t = $t->refresh();
    attachTeamsToTournament($t, 8);
    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    // 2) Generar "Tabla general" (todos contra todos)
    $phases = Phase::whereIn('name', ['Tabla general','Dieciseisavos de Final','Octavos de Final','Cuartos de Final','Semifinales','Final'])->get()->keyBy('name');
    $payloadLeague = [
        'general' => [
            'tournament_id' => $t->id,
            'tournament_format_id' => TournamentFormatId::LeagueAndElimination->value,
            'football_type_id' => 1,
            'start_date' => $startDate,
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => 8,
            'round_trip' => true,
            'group_stage' => false,
            'elimination_round_trip' => true,
            'locations' => [['id' => $location->id, 'name' => $location->name]],
        ],
        'rules_phase' => [
            'round_trip' => true,
            'tiebreakers' => $t->configuration->tiebreakers->toArray(),
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => true,
            'phases' => [
                ['id' => $phases['Tabla general']->id, 'name' => 'Tabla general', 'is_active' => true, 'is_completed' => false, 'tournament_id' => $t->id],
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
    $resLeague = $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payloadLeague)->assertOk();
    // Con elimination_round_trip=true, la liga se genera ida y vuelta → 8*7 = 56
    $resLeague->assertJsonCount(56, 'data');

    // 3) Completar juegos y generar standings globales
    $tablePhaseId = \App\Models\TournamentPhase::where('tournament_id', $t->id)
        ->join('phases','phases.id','=','tournament_phases.phase_id')
        ->where('phases.name', 'Tabla general')
        ->value('tournament_phases.id');
    $games = Game::where('tournament_id', $t->id)->where('tournament_phase_id', $tablePhaseId)->get();
    foreach ($games as $g) {
        $g->update(['home_goals' => 1, 'away_goals' => 0, 'status' => Game::STATUS_COMPLETED]);
    }

    // 4) Bracket preview (Cuartos) desde tabla general
    $preview = $this->json('GET', "/api/v1/admin/tournaments/{$t->id}/bracket/preview?phase=Cuartos de Final")
        ->assertStatus(200)
        ->json();
    expect($preview['source'])->toBe('table_standings');
    expect(count($preview['pairs']))->toBe(4);

    // 5) Activar Cuartos y confirmar 4 partidos
    $tpCuartosId = \App\Models\TournamentPhase::where('tournament_id', $t->id)
        ->join('phases','phases.id','=','tournament_phases.phase_id')
        ->where('phases.name', 'Cuartos de Final')
        ->value('tournament_phases.id');
    $this->json('PUT', "/api/v1/admin/tournaments/{$t->id}/phases/{$tpCuartosId}", [
        'is_active' => true,
        'is_completed' => false,
    ])->assertStatus(200);

    $confirmDate = Carbon::parse($startDate)->addWeeks(20)->toDateString();
    $times = ['09:00','11:00','13:00','15:00'];
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
    $this->postJson("/api/v1/admin/tournaments/{$t->id}/bracket/confirm", [
        'phase' => 'Cuartos de Final',
        'min_rest_minutes' => 120,
        'matches' => $matches,
    ])->assertStatus(200)
        ->assertJsonCount(4, 'data');
});
