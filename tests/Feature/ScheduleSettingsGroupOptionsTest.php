<?php

use App\Models\Phase;
use App\Services\GroupConfigurationOptionService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

it('exposes suggested group configuration options for odd totals', function (
    int $totalTeams,
    array $expectedSizes,
    string $expectedStage,
    bool $includeBestThirds,
    ?int $bestThirdsCount
) {
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($tournament, $totalTeams);

    $response = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/schedule/settings")
        ->assertOk()
        ->json();

    $options = collect($response['group_configuration_options']);
    expect($options)->not->toBeEmpty();

    $option = $options->first(fn (array $opt) => $opt['group_sizes'] === $expectedSizes);
    expect($option)->not->toBeNull();
    expect($option['elimination']['label'])->toBe($expectedStage);
    expect($option['group_phase']['include_best_thirds'])->toBe($includeBestThirds);
    if ($includeBestThirds) {
        expect($option['group_phase']['best_thirds_count'])->toBe($bestThirdsCount);
    } else {
        expect($option['group_phase']['best_thirds_count'])->toBeNull();
    }
})->with([
    '15 equipos' => [15, [5, 5, 5], 'Cuartos', true, 2],
    '17 equipos' => [17, [6, 6, 5], 'Cuartos', true, 2],
    '21 equipos' => [21, [6, 5, 5, 5], 'Cuartos', false, null],
    '35 equipos' => [35, [6, 6, 6, 6, 6, 5], 'Octavos', true, 4],
]);
it('only exposes homogeneous group configurations', function () {
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($tournament, 15);

    $response = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/schedule/settings")
        ->assertOk()
        ->json();

    expect($response['group_configuration_options'])->toHaveCount(2);
    expect(collect($response['group_configuration_options'])->pluck('group_sizes')->all())
        ->toBe([
            [5, 5, 5],
            [4, 4, 4, 3],
        ]);
});
it('persists group configuration when selecting a precomputed option', function () {
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($tournament, 17);
    $field = $location->fields()->first();
    $startDate = Carbon::now()->next(CarbonInterface::FRIDAY)->startOfDay()->toIso8601String();

    $service = new GroupConfigurationOptionService();
    $option = collect($service->buildOptions(17))
        ->first(fn (array $opt) => $opt['group_sizes'] === [6, 6, 5]);
    expect($option)->not->toBeNull();

    $phases = Phase::whereIn('name', ['Fase de grupos', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'])
        ->get()
        ->keyBy('name');

    $payload = [
        'general' => [
            'tournament_id' => $tournament->id,
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
            'tiebreakers' => $tournament->configuration->tiebreakers->toArray(),
        ],
        'group_phase' => [
            'option_id' => $option['id'],
        ],
        'elimination_phase' => [
            'teams_to_next_round' => 8,
            'elimination_round_trip' => true,
            'phases' => [
                ['id' => $phases['Fase de grupos']->id, 'name' => 'Fase de grupos', 'is_active' => true, 'is_completed' => false, 'tournament_id' => $tournament->id],
                ['id' => $phases['Octavos de Final']->id, 'name' => 'Octavos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $tournament->id],
                ['id' => $phases['Cuartos de Final']->id, 'name' => 'Cuartos de Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $tournament->id],
                ['id' => $phases['Semifinales']->id, 'name' => 'Semifinales', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $tournament->id],
                ['id' => $phases['Final']->id, 'name' => 'Final', 'is_active' => false, 'is_completed' => false, 'tournament_id' => $tournament->id],
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

    $this->postJson("/api/v1/admin/tournaments/{$tournament->id}/schedule", $payload)
        ->assertOk();

    $config = $tournament->fresh()->groupConfiguration;
    expect($config->teams_per_group)->toBe(6);
    expect($config->advance_top_n)->toBe(2);
    expect($config->include_best_thirds)->toBeTrue();
    expect($config->best_thirds_count)->toBe(2);
    expect($config->group_sizes)->toBe([6, 6, 5]);

    $settings = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/schedule/settings")
        ->assertOk()
        ->json();

    expect($settings['group_phase_option_id'])->toBe($option['id']);
});
