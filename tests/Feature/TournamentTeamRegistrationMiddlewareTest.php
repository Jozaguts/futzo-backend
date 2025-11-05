<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FootballType;
use App\Models\League;
use App\Models\Phase;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\TournamentFormat;
use App\Models\TournamentPhase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentTeamRegistrationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_registration_when_tournament_is_full(): void
    {
        $context = $this->createTournamentContext(maxTeams: 1);
        $this->attachTeamToTournament($context['tournament'], $context['league'], $context['category']);

        $response = $this->postJson(
            "/api/v1/public/tournaments/{$context['tournament']->id}/pre-register-team",
            [
                'team' => [
                    'name' => 'Nuevo Equipo',
                    'category_id' => $context['category']->id,
                    'tournament_id' => $context['tournament']->id,
                ],
            ]
        );

        $response->assertForbidden()
            ->assertJson([
                'message' => "El torneo {$context['tournament']->name} ha alcanzado el número máximo de equipos permitidos.",
            ]);
    }

    public function test_blocks_registration_when_tournament_is_in_elimination_phase(): void
    {
        $context = $this->createTournamentContext(maxTeams: 8);

        $phase = Phase::create([
            'name' => 'Cuartos de Final',
            'is_active' => true,
            'is_completed' => false,
        ]);

        TournamentPhase::create([
            'tournament_id' => $context['tournament']->id,
            'phase_id' => $phase->id,
            'is_active' => true,
            'is_completed' => false,
        ]);

        $response = $this->postJson(
            "/api/v1/public/tournaments/{$context['tournament']->id}/pre-register-team",
            [
                'team' => [
                    'name' => 'Equipo Eliminatorio',
                    'category_id' => $context['category']->id,
                    'tournament_id' => $context['tournament']->id,
                ],
            ]
        );

        $response->assertForbidden()
            ->assertJson([
                'message' => "El torneo {$context['tournament']->name} se encuentra en Cuartos de Final, ya no se permiten nuevos equipos.",
            ]);
    }

    private function createTournamentContext(int $maxTeams): array
    {
        $footballType = FootballType::create([
            'name' => 'Fútbol 11',
            'description' => 'Modalidad de prueba',
            'status' => 'created',
            'max_players_per_team' => 11,
            'min_players_per_team' => 7,
            'max_registered_players' => 23,
            'substitutions' => null,
        ]);

        $league = League::create([
            'name' => 'Liga Test',
            'description' => 'Descripción de prueba',
            'creation_date' => now(),
            'logo' => null,
            'banner' => null,
            'status' => League::STATUS_READY,
            'football_type_id' => $footballType->id,
        ]);

        $format = TournamentFormat::create([
            'name' => 'Formato Test',
            'description' => 'Descripción formato',
            'status' => 'created',
        ]);

        $category = Category::create([
            'name' => 'Libre',
            'age_range' => null,
            'gender' => 'male',
        ]);

        $tournament = Tournament::factory()->create([
            'league_id' => $league->id,
            'category_id' => $category->id,
            'tournament_format_id' => $format->id,
            'football_type_id' => $footballType->id,
        ]);

        TournamentConfiguration::create([
            'tournament_id' => $tournament->id,
            'tournament_format_id' => $format->id,
            'football_type_id' => $footballType->id,
            'max_teams' => $maxTeams,
        ]);

        return [
            'tournament' => $tournament,
            'league' => $league,
            'category' => $category,
        ];
    }

    private function attachTeamToTournament(Tournament $tournament, League $league, Category $category): Team
    {
        $team = Team::factory()->create();
        $team->leagues()->attach($league->id);
        $team->categories()->attach($category->id);
        $tournament->teams()->attach($team->id);

        return $team;
    }
}
