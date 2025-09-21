<?php

use App\Http\Requests\CreateTournamentScheduleRequest;
use App\Models\Tournament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

function buildGroupSchedulePayload(Tournament $tournament, array $groupSizes, ?int $totalTeams = null, ?int $teamsPerGroup = null): array
{
    $tournament->loadMissing(['configuration.tiebreakers', 'tournamentPhases.phase', 'locations.fields']);

    $location = $tournament->locations->first();
    $field = $location->fields->first();
    $totalTeams ??= array_sum($groupSizes);
    $teamsPerGroup ??= $groupSizes[0] ?? 3;

    $tiebreakers = $tournament->configuration->tiebreakers->map(static function ($tiebreaker) {
        return [
            'id' => $tiebreaker->id,
            'rule' => $tiebreaker->rule,
            'priority' => $tiebreaker->priority,
            'is_active' => (bool) $tiebreaker->is_active,
            'tournament_configuration_id' => $tiebreaker->tournament_configuration_id,
        ];
    })->values()->all();

    $phases = $tournament->tournamentPhases->map(static function ($phase) use ($tournament) {
        return [
            'tournament_id' => $tournament->id,
            'id' => $phase->phase->id,
            'name' => $phase->phase->name,
            'is_active' => (bool) $phase->is_active,
            'is_completed' => (bool) $phase->is_completed,
        ];
    })->values()->all();

    return [
        'general' => [
            'tournament_id' => $tournament->id,
            'tournament_format_id' => $tournament->tournament_format_id,
            'football_type_id' => $tournament->football_type_id,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'game_time' => 90,
            'time_between_games' => 0,
            'total_teams' => $totalTeams,
            'locations' => [
                [
                    'id' => $location->id,
                    'name' => $location->name,
                ],
            ],
        ],
        'rules_phase' => [
            'round_trip' => false,
            'tiebreakers' => $tiebreakers,
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => false,
            'phases' => $phases,
        ],
        'fields_phase' => [
            [
                'field_id' => $field->id,
                'step' => 1,
                'field_name' => $field->name,
                'location_name' => $location->name,
                'location_id' => $location->id,
                'disabled' => false,
                'availability' => [
                    'friday' => [
                        'enabled' => true,
                        'available_range' => '09:00 a 17:00',
                        'intervals' => [
                            ['value' => '09:00', 'text' => '09:00', 'selected' => true, 'disabled' => false, 'in_use' => false],
                            ['value' => '10:00', 'text' => '10:00', 'selected' => false, 'disabled' => false, 'in_use' => false],
                        ],
                        'label' => 'Viernes',
                    ],
                    'isCompleted' => true,
                ],
            ],
        ],
        'group_phase' => [
            'teams_per_group' => $teamsPerGroup,
            'advance_top_n' => 2,
            'include_best_thirds' => false,
            'best_thirds_count' => 0,
            'group_sizes' => $groupSizes,
        ],
    ];
}

function validateSchedulePayload(array $payload): \Illuminate\Contracts\Validation\Validator
{
    $request = CreateTournamentScheduleRequest::create('/api/v1/admin/tournaments/schedule', 'POST', $payload);
    $request->setContainer(app())->setRedirector(app('redirect'));

    $validator = Validator::make($request->all(), $request->rules());
    $request->withValidator($validator);

    return $validator;
}

it('rechaza tamaños de grupo menores a tres', function () {
    [$tournament] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);

    $payload = buildGroupSchedulePayload($tournament, [2, 2, 2, 2, 2, 2], 12, 2);

    $validator = validateSchedulePayload($payload);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('group_phase.group_sizes.0'))->toBeTrue();
});

it('rechaza tamaños de grupo mayores a seis', function () {
    [$tournament] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);

    $payload = buildGroupSchedulePayload($tournament, [7, 7, 7, 7, 7], 35, 7);

    $validator = validateSchedulePayload($payload);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('group_phase.group_sizes.0'))->toBeTrue();
});

it('rechaza configurar un único grupo con todos los equipos', function () {
    [$tournament] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);

    $payload = buildGroupSchedulePayload($tournament, [12], 12, 12);

    $validator = validateSchedulePayload($payload);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('group_phase.group_sizes'))->toBeTrue();
});

it('rechaza totales mayores a treinta y seis equipos', function () {
    [$tournament] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);

    $payload = buildGroupSchedulePayload($tournament, [6, 6, 6, 6, 6, 4, 3], 37, 6);

    $validator = validateSchedulePayload($payload);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('general.total_teams'))->toBeTrue();
});

it('acepta configuraciones válidas dentro de los límites', function () {
    [$tournament] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);

    $payload = buildGroupSchedulePayload($tournament, [6, 6, 6, 6, 6, 6], 36, 6);

    $validator = validateSchedulePayload($payload);

    expect($validator->passes())->toBeTrue();
});
