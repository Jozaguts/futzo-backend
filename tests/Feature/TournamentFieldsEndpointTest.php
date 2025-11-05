<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureOperationalForBilling;
use App\Models\Category;
use App\Models\Field;
use App\Models\FootballType;
use App\Models\League;
use App\Models\LeagueField;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentField;
use App\Models\TournamentFormat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TournamentFieldsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_tournament_fields_when_available(): void
    {
        $league = League::create([
            'name' => 'Liga Test',
            'status' => League::STATUS_READY,
        ]);

        $category = Category::create([
            'name' => 'Libre',
            'gender' => 'male',
        ]);

        $format = TournamentFormat::create([
            'name' => 'Grupos y Eliminatoria',
            'status' => 'created',
        ]);

        $footballType = FootballType::create([
            'name' => 'Fútbol 11',
            'description' => 'Tipo prueba',
            'status' => 'created',
            'max_players_per_team' => 11,
            'min_players_per_team' => 7,
            'max_registered_players' => 23,
            'substitutions' => null,
        ]);

        $tournamentLocation = Location::create([
            'name' => 'Estadio A',
            'address' => 'Dirección A',
            'position' => ['lat' => 0, 'lng' => 0],
            'place_id' => Str::uuid()->toString(),
        ]);
        $leagueLocation = Location::create([
            'name' => 'Estadio Liga',
            'address' => 'Dirección Liga',
            'position' => ['lat' => 2, 'lng' => 2],
            'place_id' => Str::uuid()->toString(),
        ]);

        $fieldInTournament = Field::create([
            'location_id' => $tournamentLocation->id,
            'name' => 'Campo Torneo',
            'type' => 'Fútbol 11',
            'dimensions' => ['length' => 100, 'width' => 60],
        ]);

        $leagueField = Field::create([
            'location_id' => $leagueLocation->id,
            'name' => 'Campo Liga',
            'type' => 'Fútbol 11',
            'dimensions' => ['length' => 90, 'width' => 55],
        ]);

        LeagueField::create([
            'league_id' => $league->id,
            'field_id' => $leagueField->id,
        ]);

        $tournament = Tournament::create([
            'name' => 'Torneo Apertura',
            'league_id' => $league->id,
            'category_id' => $category->id,
            'tournament_format_id' => $format->id,
            'football_type_id' => $footballType->id,
            'status' => 'creado',
        ]);

        TournamentField::create([
            'tournament_id' => $tournament->id,
            'field_id' => $fieldInTournament->id,
        ]);

        $user = User::factory()->create([
            'league_id' => $league->id,
        ]);

        Sanctum::actingAs($user);
        $this->withoutMiddleware(EnsureOperationalForBilling::class);

        $response = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/fields");

        $response->assertOk()
            ->assertJsonPath('meta.fields_source', 'tournament')
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $fieldInTournament->id])
            ->assertJsonMissing(['id' => $leagueField->id]);
    }

    public function test_it_filters_tournament_fields_by_location(): void
    {
        $league = League::create([
            'name' => 'Liga Multi Sede',
            'status' => League::STATUS_READY,
        ]);

        $category = Category::create([
            'name' => 'Libre',
            'gender' => 'male',
        ]);

        $format = TournamentFormat::create([
            'name' => 'Grupos y Eliminatoria',
            'status' => 'created',
        ]);

        $footballType = FootballType::create([
            'name' => 'Fútbol 11',
            'description' => 'Tipo prueba',
            'status' => 'created',
            'max_players_per_team' => 11,
            'min_players_per_team' => 7,
            'max_registered_players' => 23,
            'substitutions' => null,
        ]);

        $locationOne = Location::create([
            'name' => 'Sede Norte',
            'address' => 'Dirección Norte',
            'position' => ['lat' => 5, 'lng' => 5],
            'place_id' => Str::uuid()->toString(),
        ]);

        $locationTwo = Location::create([
            'name' => 'Sede Sur',
            'address' => 'Dirección Sur',
            'position' => ['lat' => -5, 'lng' => -5],
            'place_id' => Str::uuid()->toString(),
        ]);

        $fieldNorth = Field::create([
            'location_id' => $locationOne->id,
            'name' => 'Campo Norte',
            'type' => 'Fútbol 11',
            'dimensions' => ['length' => 101, 'width' => 61],
        ]);

        $fieldSouth = Field::create([
            'location_id' => $locationTwo->id,
            'name' => 'Campo Sur',
            'type' => 'Fútbol 11',
            'dimensions' => ['length' => 102, 'width' => 62],
        ]);

        $tournament = Tournament::create([
            'name' => 'Torneo Regional',
            'league_id' => $league->id,
            'category_id' => $category->id,
            'tournament_format_id' => $format->id,
            'football_type_id' => $footballType->id,
            'status' => 'creado',
        ]);

        TournamentField::insert([
            [
                'tournament_id' => $tournament->id,
                'field_id' => $fieldNorth->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tournament_id' => $tournament->id,
                'field_id' => $fieldSouth->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $user = User::factory()->create([
            'league_id' => $league->id,
        ]);

        Sanctum::actingAs($user);
        $this->withoutMiddleware(EnsureOperationalForBilling::class);

        $northResponse = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/fields?location_id={$locationOne->id}");

        $northResponse->assertOk()
            ->assertJsonPath('meta.fields_source', 'tournament')
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $fieldNorth->id]);
        $this->assertSame(
            [$fieldNorth->id],
            collect($northResponse->json('data'))->pluck('id')->all()
        );

        $southResponse = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/fields?location_id={$locationTwo->id}");

        $southResponse->assertOk()
            ->assertJsonPath('meta.fields_source', 'tournament')
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $fieldSouth->id]);
        $this->assertSame(
            [$fieldSouth->id],
            collect($southResponse->json('data'))->pluck('id')->all()
        );
    }

    public function test_it_falls_back_to_league_fields_when_tournament_has_none(): void
    {
        $league = League::create([
            'name' => 'Liga Fallback',
            'status' => League::STATUS_READY,
        ]);

        $category = Category::create([
            'name' => 'Libre',
            'gender' => 'male',
        ]);

        $format = TournamentFormat::create([
            'name' => 'Liga',
            'status' => 'created',
        ]);

        $footballType = FootballType::create([
            'name' => 'Fútbol 11',
            'description' => 'Tipo prueba',
            'status' => 'created',
            'max_players_per_team' => 11,
            'min_players_per_team' => 7,
            'max_registered_players' => 23,
            'substitutions' => null,
        ]);

        $location = Location::create([
            'name' => 'Estadio B',
            'address' => 'Dirección B',
            'position' => ['lat' => 1, 'lng' => 1],
            'place_id' => Str::uuid()->toString(),
        ]);

        $leagueField = Field::create([
            'location_id' => $location->id,
            'name' => 'Campo Disponible',
            'type' => 'Fútbol 11',
            'dimensions' => ['length' => 110, 'width' => 65],
        ]);

        LeagueField::create([
            'league_id' => $league->id,
            'field_id' => $leagueField->id,
        ]);

        $tournament = Tournament::create([
            'name' => 'Torneo Clausura',
            'league_id' => $league->id,
            'category_id' => $category->id,
            'tournament_format_id' => $format->id,
            'football_type_id' => $footballType->id,
            'status' => 'creado',
        ]);

        $user = User::factory()->create([
            'league_id' => $league->id,
        ]);

        Sanctum::actingAs($user);
        $this->withoutMiddleware(EnsureOperationalForBilling::class);

        $response = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/fields");

        $response->assertOk()
            ->assertJsonPath('meta.fields_source', 'league')
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $leagueField->id]);

        $filteredResponse = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/fields?location_id={$location->id}");

        $filteredResponse->assertOk()
            ->assertJsonPath('meta.fields_source', 'league')
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $leagueField->id]);
    }
}
