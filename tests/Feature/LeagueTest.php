<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeagueTest extends TestCase
{
    /**
     * validate store league
     */
    public function test_store_league()
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('logo-test.jpg');
        $banner = UploadedFile::fake()->image('banner-test.jpg');
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/admin/leagues', [
            'name' => 'Torneo 1',
            'location' => 'Acapulco',
            'description' => 'Torneo de futbol',
            'creation_date' => '2021-10-10',
            'logo' => $logo,
            'banner' => $banner,
            'status' => 'active',
        ]);

        Storage::disk('public')->assertExists('/images/'.$logo->hashName());
        Storage::disk('public')->assertExists('/images/'.$banner->hashName());

        $response->assertStatus(201);
    }

    public function test_store_league_required_name()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/admin/leagues', [
            'location' => 'Acapulco',
            'description' => 'Torneo de futbol',
            'creation_date' => '2021-10-10',
            'status' => 'active',
        ]);

        $response->assertStatus(422);
    }

    public function test_index_league()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/admin/leagues');

        $response->assertStatus(200)
            ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'description',
                'creation_date',
                'logo',
                'banner',
                'status',
                'location',
                'tournament_count'
            ]
        ]);
    }
}
