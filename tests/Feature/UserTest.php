<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InitUser;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, InitUser;


    public function test_register_user()
    {
        $this->json('POST', '/auth/register', [
            'name' => 'John',
            'email' => 'test@test.com',
            'password' => 'password'
            ])->dump();

        $this->assertDatabaseHas('users', [
            'name' => 'John',
            'email' => 'test@test.com'
        ]);
    }
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
                'email',
                'roles',
                'league'
            ]);
    }



}
