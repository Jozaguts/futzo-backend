<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\InitUser;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase, InitUser;

    use RefreshDatabase;

    public function test_admin_can_generate_tournament_schedule(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);
        $admin->assignRole('administrador');
        $league = League::factory()->create();
        $admin->league_id = $league->id;
        $tournament = Tournament::withoutEvents(static fn() => Tournament::factory()->create());

        $response = $this->actingAs($admin)->json('POST', '/api/v1/admin/schedule/generate', [
            'league_id' => $league->id,
            'tournament_id' => $tournament->id,
        ]);

    }

    public function test_super_admin_can_generate_tournament_schedule(): void
    {
        $superAdmin = User::factory()->create();
        Sanctum::actingAs($superAdmin);
        $superAdmin->assignRole('super administrador');
        $league = League::factory()->create();
        $superAdmin->league_id = $league->id;

        $tournament = Tournament::withoutEvents(static fn() => Tournament::factory()->create());

        $response = $this->actingAs($superAdmin)->json('POST', '/api/v1/admin/schedule/generate', [
            'league_id' => $league->id,
            'tournament_id' => $tournament->id,
        ]);

    }

    // todo  test_non_admin_cannot_generate_tournament_schedule
}
