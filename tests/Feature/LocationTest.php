<?php

namespace Tests\Feature;

beforeEach(function () {
    $this->user = $this->initUser();
});

it('stores a location correctly', function () {
    $response = $this->postJson('/api/v1/admin/locations', [
        'name' => 'Location 1',
        'city' => 'City 1',
        'address' => 'Address 1',
        'autocomplete_prediction' => [
            'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
            'description' => 'Location 1, City 1, Address 1',
        ],
        'position' => [
            'lat' => -34.397,
            'lng' => 150.644,
        ],
    ]);

    $response->assertCreated();
});
