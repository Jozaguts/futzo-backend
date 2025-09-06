<?php

namespace Tests\Feature;


it('stores a location correctly', function () {
    $response = $this->postJson('/api/v1/admin/locations', [
        'name' => 'Location 1',
        'address' => 'Address 1',
        'position' => [
            'lat' => -34.397,
            'lng' => 150.644,
        ],
    ]);

    $response->assertCreated();
});
