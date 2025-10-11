<?php

use App\Enums\FootballTypeId;
use App\Support\MatchDuration;

it('calculates fut 7 duration without admin gap', function () {
    $config = (object) [
        'game_time' => 60,
        'time_between_games' => 0,
        'football_type_id' => FootballTypeId::SevenFootball->value,
    ];

    expect(MatchDuration::minutes($config))->toBe(60);
});

it('adds admin gap to fut 7 duration when present', function () {
    $config = (object) [
        'game_time' => 60,
        'time_between_games' => 15,
        'football_type_id' => FootballTypeId::SevenFootball->value,
    ];

    expect(MatchDuration::minutes($config))->toBe(75);
});

it('applies standard buffer for fut 11 matches', function () {
    $config = (object) [
        'game_time' => 90,
        'time_between_games' => 0,
        'football_type_id' => FootballTypeId::TraditionalFootball->value,
    ];

    expect(MatchDuration::minutes($config))->toBe(120);
});
