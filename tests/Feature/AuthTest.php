<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
beforeEach(function () {
    $this->user = User::factory()->create();
});
test('el usuario puede iniciar sesión con credenciales válidas', function () {

    $user = User::create([
        'name' => 'Nombre de usuario',

    ]);
//
//    $response = json('POST', '/api/login', [
//        'email' => 'test@example.com',
//        'password' => 'password',
//    ]);
//
//
//    $response->assertStatus(200);
//    $response->assertJson(['success' => true]);


//    expect(auth()->user())->toBe($user);
});
