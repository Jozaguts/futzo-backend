<?php

use App\Enums\TournamentFormatId;
use App\Models\Field;
use Illuminate\Support\Str;

it('expands 00:00 end time to 24:00 availability windows', function () {
    $payload = [
        'name' => 'Sede Horarios 24h',
        'address' => 'Calle Prueba 123, Guadalajara, Jal., México',
        'place_id' => (string) Str::uuid(),
        'position' => [
            'lat' => 20.678206,
            'lng' => -103.340885,
        ],
        'fields' => [
            [
                'name' => 'Campo 1',
                'windows' => [
                    'mon' => [
                        ['start' => '09:00', 'end' => '00:00'],
                    ],
                    'tue' => [
                        ['start' => '09:00', 'end' => '00:00'],
                    ],
                    'wed' => [
                        ['start' => '09:00', 'end' => '00:00'],
                    ],
                    'thu' => [
                        ['start' => '09:00', 'end' => '00:00'],
                    ],
                    'fri' => [
                        ['start' => '09:00', 'end' => '00:00'],
                    ],
                    'sat' => [
                        ['start' => '09:00', 'end' => '00:00'],
                    ],
                    'sun' => [
                        ['start' => '09:00', 'end' => '00:00'],
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

    $fieldsResponse = $this->getJson(
        sprintf('/api/v1/admin/locations/fields?location_ids=%d&tournament_id=%d', $locationId, $tournament->id)
    );

    $fieldsResponse
        ->assertOk()
        ->assertJsonPath('0.field_id', $field->id)
        ->assertJsonPath('0.availability.monday.enabled', true)
        ->assertJsonPath('0.availability.monday.available_range', '09:00 a 24:00')
        ->assertJsonPath('0.availability.monday.intervals.0.value', '09:00');
});
