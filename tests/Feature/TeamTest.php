<?php

namespace Tests\Feature;

use App\Models\TeamDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
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
            'name' => 'Team test',
            'tournament_id' => 1,
            'category_id' => 1,
            'president_name' => 'John Doe',
            'coach_name' => 'John Doe',
            'phone' => 'John Doe',
            'email' => 'test@test.com',
            'address' => 'address',
            'image' => $image,
            'colors' => $expectedColors,
         ]);
         $response->assertStatus(201);
         $this->assertDatabaseHas('teams', [
            'name' => 'Team 1',
         ]);

        $teamDetail = TeamDetail::where('team_id', $response->json('id'))->first();

        $this->assertNotNull($teamDetail);
        $this->assertEquals($expectedColors, $teamDetail->colors);
        Storage::disk('public')->assertExists('/images/'.$image->hashName());
    }
}
