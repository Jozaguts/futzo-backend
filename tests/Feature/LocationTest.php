<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
            'availability' => [
                'monday' => [
                    'start' => '08:00',
                    'end' => '18:00',
                ],
                'tuesday' => [
                    'start' => '08:00',
                    'end' => '18:00',
                ],
                'wednesday' => [
                    'start' => '08:00',
                    'end' => '18:00',
                ],
                'thursday' => [
                    'start' => '08:00',
                    'end' => '18:00',
                ],
                'friday' => [
                    'start' => '08:00',
                    'end' => '18:00',
                ],
                'saturday' => [
                    'start' => '08:00',
                    'end' => '18:00',
                ],
                'sunday' => [
                    'start' => '08:00',
                    'end' => '18:00',
                ],
            ],
        ]);

        $response->assertStatus(201);
    }
}
