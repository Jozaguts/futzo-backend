<?php

use App\Services\GroupConfigurationOptionService;

it('builds expected group configuration options for odd totals', function (
    int $totalTeams,
    array $expectedSizes,
    int $advanceTopN,
    bool $includeBestThirds,
    ?int $bestThirdsCount,
    string $expectedStage
) {
    $service = new GroupConfigurationOptionService();
    $options = collect($service->buildOptions($totalTeams));

    expect($options)->not->toBeEmpty();

    $option = $options->first(fn (array $opt) => $opt['group_sizes'] === $expectedSizes);
    expect($option)->not->toBeNull();

    expect($option['group_phase']['advance_top_n'])->toBe($advanceTopN);
    expect($option['group_phase']['include_best_thirds'])->toBe($includeBestThirds);
    if ($includeBestThirds) {
        expect($option['group_phase']['best_thirds_count'])->toBe($bestThirdsCount);
    } else {
        expect($option['group_phase']['best_thirds_count'])->toBeNull();
    }
    expect($option['elimination']['label'])->toBe($expectedStage);
})->with([
    '15 equipos (5-5-5)' => [15, [5, 5, 5], 2, true, 2, 'Cuartos'],
    '17 equipos (6-6-5)' => [17, [6, 6, 5], 2, true, 2, 'Cuartos'],
    '21 equipos (6-5-5-5)' => [21, [6, 5, 5, 5], 2, false, null, 'Cuartos'],
    '35 equipos (6-6-6-6-6-5)' => [35, [6, 6, 6, 6, 6, 5], 2, true, 4, 'Octavos'],
]);

it('prioritizes homogeneous group sizes and caps the number of options', function () {
    $service = new GroupConfigurationOptionService();

    $optionsForFifteen = collect($service->buildOptions(15))->pluck('group_sizes')->all();
    expect($optionsForFifteen)->toBe([
        [5, 5, 5],
        [4, 4, 4, 3],
    ]);

    $optionsForThirtyFive = collect($service->buildOptions(35))->pluck('group_sizes')->all();
    expect($optionsForThirtyFive)->toBe([
        [5, 5, 5, 5, 5, 5, 5],
        [6, 6, 6, 6, 6, 5],
        [5, 5, 5, 4, 4, 4, 4, 4],
    ]);
});
