<?php

namespace Tests\Feature;

use App\Models\User;

it('store user', function () {
    $this->json('POST', '/auth/register', [
        'name' => 'John',
        'email' => 'test@test.com',
        'password' => 'password'
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'John',
        'email' => 'test@test.com',
    ]);
});

it('get users with pagination', function () {
    $response = $this->getJson('/api/v1/me');

    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'name',
            'email',
            'roles',
            'league'
        ]);
});
it('get user by id', function () {
    $user = User::first();
    $response = $this
        ->actingAs($user)
        ->json('PUT', "/api/v1/admin/profile/$user->id", [
            'name' => 'John',
            'email' => 'test2@gmail.com',
        ]);
    $response->assertStatus(200);
});

it('get user by id with wrong id', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $response = $this
        ->actingAs($user1)
        ->json('PUT', "/api/v1/admin/profile/$user2->id", [
            'name' => 'John',
            'email' => 'test@gmail.com',
            'phone' => '+523222397179',
        ]);
    $response->assertStatus(403);
});
