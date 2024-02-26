<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
       $this->initUser();

         $response = $this->json('POST', '/api/v1/admin/teams', [
              'name' => 'Team 1',
              'tournament_id' => 1,
         ]);
         $response->dump();
    }
}
