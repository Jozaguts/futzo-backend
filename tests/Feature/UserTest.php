<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_endpoint()
    {
        $user = User::factory()->create();


        $response = $this
            ->actingAs($user)
            ->json('GET', '/api/v1/me');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'name',
                'lastname',
                'email',
                'roles',
                'league'
            ]);
    }


}
