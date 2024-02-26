<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InitUser;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, InitUser;

    public function test_me_endpoint()
    {
        $user = $this->initUser();


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
