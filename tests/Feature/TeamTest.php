<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\InitUser;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase, InitUser;
    /**
     * A basic feature test example.
     */
    public function test_store_team(): void
    {

        Storage::fake('public');
        $image = UploadedFile::fake()->image('logo-test.jpg')->mimeType('image/jpeg');
        $coachImage = UploadedFile::fake()->image('coach-test.jpg')->mimeType('image/jpeg');
        $address = fake()->address;
        $this->initUser();
        $expectedColors = [
            'home' => [
                'jersey' => 'red',
                'short' => 'red',
            ],
            'away' => [
                'jersey' => 'blue',
                'short' => 'blue',
            ],
        ];

        $response = $this->json('POST', '/api/v1/admin/teams', [
            'team' =>[
                'name' => 'Team 1',
                'address' => $address,
                'image' => $image,
                'email' => fake()->email,
                'phone' => fake()->phoneNumber,
                'colors' => $expectedColors,
                'category_id' => 1,
            ],
            'president' => [
                'name' => 'John Doe',
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
            ],
            'coach' => [
                'name' => 'John Doe',
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
                'image' => $coachImage,
            ],
         ]);
         $response->assertStatus(201);

         $this->assertDatabaseHas('teams', [
             'name' => 'Team 1',
             'colors->home->jersey' => 'red',
             'colors->home->short' => 'red',
             'colors->away->jersey' => 'blue',
             'colors->away->short' => 'blue',
         ]);
         $this->assertDatabaseHas('users', [
             'name' => 'John Doe',
             'email' => $response->json('coach.email'),
             'phone' => $response->json('coach.phone'),
         ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => $response->json('president.email'),
            'phone' => $response->json('president.phone'),
        ]);



        Storage::disk('public')->assertExists('/images/'.$image->hashName());
    }
}
