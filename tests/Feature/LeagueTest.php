<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;


beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});
it('store a league and upload logo and banner', function () {
    Storage::fake('public');
    $logo = UploadedFile::fake()->image('logo-test.jpg');
    $banner = UploadedFile::fake()->image('banner-test.jpg');
    $response = $this
        ->postJson('/api/v1/admin/leagues', [
            'name' => 'Apertura' . now()->format('Y'),
            'football_type_id' => 1,
            'location' => 'Acapulco',
            'description' => 'Torneo de futbol',
            'creation_date' => '2021-10-10',
            'logo' => $logo,
            'banner' => $banner,
            'status' => 'active',
        ]);

    $response->assertCreated();

    $data = $response->json();

    Storage::disk('public')->assertExists($data['logo']);
    Storage::disk('public')->assertExists($data['banner']);

    $response->assertCreated();
});

it('validates that the name is required', function () {
    $response = $this->postJson('/api/v1/admin/leagues', [
        'location' => 'Acapulco',
        'description' => 'Torneo de futbol',
        'creation_date' => '2021-10-10',
        'status' => 'active',
    ]);
    $response->assertStatus(422);
});

it('list the leagues correctly', function () {
    $response = $this->getJson('/api/v1/admin/leagues');
    $response->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'football_type_id',
                'description',
                'creation_date',
                'logo',
                'banner',
                'status',
                'location',
                'tournament_count'
            ]
        ]);
});
