<?php

use App\Models\League;
use App\Models\QrConfiguration;
use App\Models\QrType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;


beforeEach(function () {
    $this->withoutMiddleware();
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
            'status' => League::STATUS_DRAFT,
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

it('creates qr configurations for every qr type when a league is created', function () {
    QrConfiguration::query()->delete();
    QrType::query()->delete();

    $qrTypes = collect([
        ['name' => 'Registro de equipos', 'key' => 'team_registration', 'description' => 'QR para registro de equipos'],
        ['name' => 'Registro de torneos', 'key' => 'tournament_registration', 'description' => 'QR para registro de torneos'],
    ])->map(fn(array $payload) => QrType::create($payload));

    $league = League::create([
        'name' => 'Liga QR',
        'status' => League::STATUS_DRAFT,
    ]);

    expect($league->QRConfigurations)->toHaveCount($qrTypes->count());

    $qrTypes->each(function (QrType $type) use ($league): void {
        $this->assertDatabaseHas('qr_configurations', [
            'league_id' => $league->id,
            'qr_type_id' => $type->id,
            'title' => $league->name,
            'subtitle' => 'Configuración inicial',
        ]);
    });
});
