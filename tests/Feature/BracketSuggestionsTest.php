<?php

use App\Models\Game;
use App\Models\Phase;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

it('suggests free slots for bracket scheduling and reflects conflicts', function () {
    // Torneo Grupos+Eliminatoria, 8 equipos, con ventanas viernes/sábado
    [$t, $location] = createTournamentViaApi(5, 1, null, null);
    attachTeamsToTournament($t, 8);
    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $phases = Phase::whereIn('name', ['Tabla general','Fase de grupos','Cuartos de Final'])->get()->keyBy('name');
    $payload = [
        'general' => [
            'tournament_id' => $t->id,
            'tournament_format_id' => 5,
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
            'tiebreakers' => $t->refresh()->configuration->tiebreakers->toArray(),
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
                ['id' => $phases['Tabla general']->id, 'name' => 'Tabla general', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Fase de grupos']->id, 'name' => 'Fase de grupos', 'is_active' => true, 'is_completed' => false, 'tournament_id' => $t->id],
                ['id' => $phases['Cuartos de Final']->id, 'name' => 'Cuartos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $t->id],
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
    $this->postJson("/api/v1/admin/tournaments/{$t->id}/schedule", $payload)->assertOk();

    // Sugerencias para una fecha de viernes dentro del bloque
    $suggestDate = Carbon::parse($startDate)->addWeeks(4)->toDateString();
    $res = $this->json('GET', "/api/v1/admin/tournaments/{$t->id}/bracket/suggestions?date={$suggestDate}")
        ->assertStatus(200)
        ->json();
    $firstField = collect($res['suggestions'])->firstWhere('field_id', $field->id);
    expect($firstField)->not->toBeNull();
    expect($firstField['slots'])->toContain('09:00','11:00','13:00','15:00');

    // Crear un juego a las 11:00 para bloquear ese slot y verificar que desaparezca
    Game::create([
        'tournament_id' => $t->id,
        'league_id' => auth()->user()->league_id,
        'home_team_id' => $t->teams()->first()->id,
        'away_team_id' => $t->teams()->skip(1)->first()->id,
        'field_id' => $field->id,
        'location_id' => $location->id,
        'match_date' => $suggestDate,
        'match_time' => '11:00:00',
        'status' => Game::STATUS_SCHEDULED,
        'round' => 1,
    ]);

    $res2 = $this->json('GET', "/api/v1/admin/tournaments/{$t->id}/bracket/suggestions?date={$suggestDate}")
        ->assertStatus(200)
        ->json();
    $firstField2 = collect($res2['suggestions'])->firstWhere('field_id', $field->id);
    expect($firstField2['slots'])->not->toContain('11:00');

    // Asegura que no se generaron juegos KO automáticamente al terminar la fase previa
    $koPhaseId = \App\Models\TournamentPhase::where('tournament_id', $t->id)
        ->join('phases','phases.id','=','tournament_phases.phase_id')
        ->where('phases.name','Cuartos de Final')
        ->value('tournament_phases.id');
    $koGames = Game::where('tournament_id', $t->id)->where('tournament_phase_id', $koPhaseId)->count();
    expect($koGames)->toBe(0);
});

