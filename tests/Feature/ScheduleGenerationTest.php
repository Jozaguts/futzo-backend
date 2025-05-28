<?php

use App\Models\Tournament;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

it('genera un calendario para 16 equipos en liga ida y vuelta', function () {
    // 1) Prepara usuario y liga
    $tournament = Tournament::first();
    $location = $tournament->locations()->first();
    $fields = $location->fields()->where('id', 1)->get();
    $payload = [
        'general' => [
            'tournament_id' => $tournament->id,
            'tournament_format_id' => 1,
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
                    'intervals' => array_map(fn($h) => ['value' => $h, 'text' => $h, 'selected' => $i === 0, 'disabled' => false],
                        ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00']),
                    'label' => 'Viernes',
                ],
                'saturday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => array_map(fn($h) => ['value' => $h, 'text' => $h, 'selected' => $i === 0, 'disabled' => false],
                        ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00']),
                    'label' => 'Sábado',
                ],
                'sunday' => [
                    'enabled' => true,
                    'available_range' => '09:00 a 17:00',
                    'intervals' => array_map(fn($h) => ['value' => $h, 'text' => $h, 'selected' => $i === 0, 'disabled' => false],
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
it('no permite crear un calendario si las horas ya están reservadas', function () {
    // Reutilizamos el payload del primer test
    $tournament = Tournament::find(2);
    $location = $tournament->locations()->first();
    $fields = $location->fields()->where('id', 1)->get();
    $startDateString = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $payload = [
        'general' => [
            'tournament_id' => $tournament->id,
            'tournament_format_id' => 1,
            'football_type_id' => 1,
            'start_date' => $startDateString,
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
                    'intervals' => array_map(fn($h) => [
                        'value' => $h,
                        'text' => $h,
                        'selected' => true,  // aquí da igual
                        'disabled' => false,
                    ], ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00']),
                    'label' => 'Viernes',
                ],
                // ... mismo para sábado y domingo
                'isCompleted' => true,
            ],
        ], $fields->toArray(), array_keys($fields->toArray())),
    ];

    // Intentamos reservar exactamente las mismas horas
    $response = $this
        ->postJson("/api/v1/admin/tournaments/{$tournament->id}/schedule", $payload);

    // Esperamos un error de según tu implementación
    $response->assertStatus(500);

    // Por ejemplo, validamos que el error venga sobre los intervals
    $response->assertJson([
        'message' => 'La cantidad de horas seleccionadas no son suficientes para generar completamente las jornadas del calendario. Por favor, ajuste la disponibilidad de los campos o el número de equipos.',
    ]);
});
