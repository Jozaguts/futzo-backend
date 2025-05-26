<?php

use App\Models\Category;
use App\Models\DefaultTournamentConfiguration;
use App\Models\FootballType;
use App\Models\League;
use App\Models\Location;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Generamos un segundo torneo en la misma liga y ubicación

    $league = League::firstOrFail();
    $category = Category::firstOrFail();

    // Tomamos la configuración “por defecto” previamente sembrada
    $defaultConfig = DefaultTournamentConfiguration::firstOrFail();

    // Relacionados belongsTo
    $format = TournamentFormat::findOrFail($defaultConfig->tournament_format_id);
    $fType = FootballType::findOrFail($defaultConfig->football_type_id);


    // Creamos el torneo vinculando las relaciones belongsTo
    $this->secondTournamentournament = Tournament::factory()
        ->for($league)                         // league_id
        ->for($category)                       // category_id
        ->for($format, 'format')               // tournament_format_id
        ->for($fType, 'footballType')          // football_type_id
        ->create([
            'league_id' => $this->user->league_id,
        ]);
    $locationIds = Location::pluck('id');
    $this->secondTournamentournament->locations()->sync($locationIds);

    // Creamos la configuración detallada con los mismos valores
    $this->secondTournamentournament->configuration()->create([
        'tournament_format_id' => $defaultConfig->tournament_format_id,
        'football_type_id' => $defaultConfig->football_type_id,
        'game_time' => $defaultConfig->game_time,
        'time_between_games' => $defaultConfig->time_between_games,
        'max_teams' => $defaultConfig->max_teams,
        'min_teams' => $defaultConfig->min_teams,
        'round_trip' => $defaultConfig->round_trip,
        'group_stage' => $defaultConfig->group_stage,
        'max_players_per_team' => $defaultConfig->max_players_per_team,
        'min_players_per_team' => $defaultConfig->min_players_per_team,
        'max_teams_per_player' => $defaultConfig->max_teams_per_player,
        'elimination_round_trip' => $defaultConfig->elimination_round_trip,
    ]);

    // Insertamos los tiebreakers
    collect(config('constants.tiebreakers'))->each(function ($tb) {
        $this->secondTournamentournament->configuration
            ->tiebreakers()
            ->create(array_merge($tb, [
                'tournament_configuration_id' => $this->secondTournamentournament->configuration->id,
            ]));
    });

    // Insertamos fases según el formato
    $allPhases = collect(config('constants.phases'));
    if ($this->secondTournamentournament->format->name === 'Torneo de Liga') {
        // Solo "Tabla general"
        $phase = $allPhases->firstWhere('name', 'Tabla general');
        $this->secondTournamentournament->phases()->create($phase);
    } else {
        // Todas menos "Tabla general"
        $allPhases
            ->reject(fn($p) => $p['name'] === 'Tabla general')
            ->each(fn($p) => $this->secondTournamentournament->phases()->create($p));
    }
    Team::factory()
        ->count(16)
        // Generamos un presidente y un coach diferentes para cada equipo
        ->state(function () use ($league) {
            $coach = User::factory()->create(['league_id' => $league->id]);
            $president = User::factory()->create(['league_id' => $league->id]);

            return [
                'coach_id' => $coach->id,
                'president_id' => $president->id,
            ];
        })
        // Relaciones belongsToMany
        ->hasAttached($league, [], 'leagues')
        ->hasAttached($category, [], 'categories')
        ->hasAttached($this->secondTournamentournament, [], 'tournaments')
        ->create();
});
it('genera un calendario para 16 equipos en liga ida y vuelta', function () {
    // 1) Prepara usuario y liga
    $tournament = Tournament::first();
    $location = $tournament->locations()->first();
    $fields = $location->fields()->get();
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
            'phases' => $tournament->phases->toArray(),
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
    $tournament = Tournament::first();
    $location = $tournament->locations()->first();
    $fields = $location->fields()->get();
    $startDateString = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $payload = [
        'general' => [
            'tournament_id' => $this->secondTournamentournament->id,
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
            'tiebreakers' => $this->secondTournamentournament->configuration->tiebreakers->toArray(),
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'round_trip' => false,
            'phases' => $this->secondTournamentournament->phases->toArray(),
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
                        'selected' => false,  // aquí da igual
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
        ->postJson("/api/v1/admin/tournaments/{$this->secondTournamentournament->id}/schedule", $payload);

    // Esperamos un error de validación (422) o conflicto (409) según tu implementación
    $response->assertStatus(422);

    // Por ejemplo, validamos que el error venga sobre los intervals
    $response->assertJsonValidationErrors([
        'fields_phase.0.availability.friday.intervals.0',
        'fields_phase.0.availability.saturday.intervals.0',
        // ...
    ]);
});
