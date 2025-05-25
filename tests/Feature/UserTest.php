<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InitUser;
use Tests\TestCase;

it('get users', function () {
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
});

it('get users with pagination', function () {
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
});
it('get user by id', function () {
    $user = $this->initUser();

    $response = $this
        ->actingAs($user)
        ->json('PUT', "/api/v1/admin/profile/$user->id", [
            'name' => 'John',
            'email' => 'test2@gmail.com',
        ]);
    $response->assertStatus(200);
});

it('get user by id with wrong id', function () {
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
});
