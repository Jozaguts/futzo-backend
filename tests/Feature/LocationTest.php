<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InitUser;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase, InitUser;

    public function test_store_locations()
    {
        $this->initUser();

        $response = $this->json('POST', '/api/v1/admin/locations', [
            'name' => 'Location 1',
            'city' => 'City 1',
            'address' => 'Address 1',
            'autocomplete_prediction' => [
                'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
            ]
        ]);

        $response->assertStatus(201);
    }
}
