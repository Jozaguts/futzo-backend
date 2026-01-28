<?php

use App\Enums\TournamentFormatId;
use App\Models\Field;
use App\Models\Game;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

it('returns available options for the selected date and field', function () {
    $payload = [
        'name' => 'Sede Programacion',
        'address' => 'Av. Test 456, Guadalajara, Jal., México',
        'place_id' => (string) Str::uuid(),
        'position' => [
            'lat' => 20.678206,
            'lng' => -103.340885,
        ],
        'fields' => [
            [
                'name' => 'Campo A',
                'windows' => [
                    'mon' => [
                        ['start' => '09:00', 'end' => '12:00'],
                    ],
                ],
            ],
        ],
        'fields_count' => 1,
        'steps' => [
            'location' => ['completed' => true],
            'fields' => ['completed' => true],
        ],
    ];

    $response = $this->postJson('/api/v1/admin/locations', $payload);
    $response->assertOk();

    $locationId = $response->json('id');
    $field = Field::where('location_id', $locationId)->firstOrFail();

    [$tournament] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, $locationId);
    attachTeamsToTournament($tournament, 2);

    $tournament->refresh();
    $teams = $tournament->teams()->take(2)->get()->values();
    $activePhase = $tournament->activePhase();

    $matchDate = Carbon::now()->next(CarbonInterface::MONDAY)->toDateString();

    $game = Game::create([
        'tournament_id' => $tournament->id,
        'league_id' => $tournament->league_id,
        'home_team_id' => $teams[0]->id,
        'away_team_id' => $teams[1]->id,
        'round' => 1,
        'match_date' => $matchDate,
        'match_time' => null,
        'location_id' => $locationId,
        'field_id' => $field->id,
        'status' => Game::STATUS_SCHEDULED,
        'tournament_phase_id' => $activePhase?->id,
    ]);

    $detailsResponse = $this->getJson(
        "/api/v1/admin/games/{$game->id}/details?date={$matchDate}&field_id={$field->id}"
    );

    $detailsResponse
//        ->assertOk()
        ->assertJsonPath('details.day_of_week', 'monday')
        ->assertJsonPath('options.0.field_id', $field->id)
        ->assertJsonPath('options.0.available_intervals.day', 'monday')
        ->assertJsonPath('options.0.available_intervals.hours.0.start', '09:00');
});
