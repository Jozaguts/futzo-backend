<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use App\Models\Category;
use App\Models\FootballType;
use App\Models\Location;
use App\Models\TournamentFormat;
use App\Models\Tournament;
use App\Models\Team;
use Illuminate\Http\UploadedFile;

uses(
    TestCase::class,
    RefreshDatabase::class,
)
    ->beforeEach(function () {
        $this->user = User::first();
        // asegurar autenticación y estado operativo para pasar middleware de billing
        Sanctum::actingAs($this->user, ['*']);
        $this->user->status = \App\Models\User::ACTIVE_STATUS;
        if (is_null($this->user->league_id)) {
            $this->user->league_id = 1; // fallback al primer registro
        }
        $this->user->saveQuietly();

        $this->app
            ->make(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @throws JsonException
 */
function createTournamentViaApi(int $formatId = 1, int $footballTypeId = 1, ?int $categoryId = null, ?int $locationId = null): array
{
    $formats = array_map(function (array $format) { return TournamentFormat::updateOrcreate(['id' => $format['id']], $format); }, config('constants.tournament_formats'));
    $footballTypes = array_map(function (array $type) { return FootballType::updateOrcreate(['id' => $type['id']], $type); }, config('constants.football_types'));
    $category = $categoryId ? Category::findOrFail($categoryId) : Category::firstOrFail();
    $location = $locationId ? Location::findOrFail($locationId) : Location::firstOrFail();

    $image = UploadedFile::fake()->image('logo-test.jpg')->mimeType('image/jpeg');
    $name = 'Torneo ' . ($formatId === 2 ? 'Liga+Elim' : 'Liga') . ' ' . uniqid('', true);
    $response = test()->postJson('/api/v1/admin/tournaments', [
        'basic' => [
            'name' => $name,
            'image' => $image,
            'tournament_format_id' => $formatId,
            'substitutions_per_team' => 3,
            'football_type_id' => $footballTypeId,
            'category_id' => $category->id,
            'start_date' => now()->addDays(3)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'min_max' => json_encode([8, 32], JSON_THROW_ON_ERROR | true),
        ],
        'details' => [
            'prize' => 'Premio de prueba',
            'winner' => null,
            'description' => 'Descripción de prueba',
            'status' => null,
            'locationIds' => json_encode([$location->id], JSON_THROW_ON_ERROR | true),
        ],
    ]);

    $response->assertCreated();
    $data = $response->json();
    $tournament = Tournament::findOrFail($data['id']);
    return [$tournament, $location];
}

function attachTeamsToTournament(Tournament $tournament, int $count = 16): void
{
    $category = Category::firstOrFail();
    $leagueId = $tournament->league_id ?? 1;
    $league = \App\Models\League::findOrFail($leagueId);
    Team::factory()
        ->count($count)
        ->hasAttached($league, [], 'leagues')
        ->hasAttached($category, [], 'categories')
        ->hasAttached($tournament, [], 'tournaments')
        ->create();
}
