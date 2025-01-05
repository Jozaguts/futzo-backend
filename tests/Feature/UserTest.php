<?php

namespace Tests\Feature;

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
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John',
            'email' => 'test@test.com',
            'image' => 'https://ui-avatars.com/api/?name=John&color=9155fd&background=F9FAFB'
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

    public function test_edit_user()
    {
        $user = $this->initUser();

        $response = $this
            ->actingAs($user)
            ->json('PUT', "/api/v1/admin/profile/$user->id", [
                'name' => 'John',
                'email' => 'test2@gmail.com',
            ]);
        $response->assertStatus(200);
    }

    public function test_ensure_user_owns_profile()
    {
        $user = $this->initUser();
        $user2 = $this->initUser();

        $response = $this
            ->actingAs($user2)
            ->json('PUT', "/api/v1/admin/profile/$user->id", [
                'name' => 'John',
                'email' => 'test@gmail.com',
                'phone' => '+523222397179',
            ]);
        $response->assertStatus(403);
    }

}
