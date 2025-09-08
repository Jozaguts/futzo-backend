<?php

use App\Models\User;

it('accepts fixed OTP 1111 for @playwright.test emails in testing env', function () {
    // Given a user with a test-domain email and a random verification_token
    $user = User::factory()->create([
        'email' => 'tester@playwright.test',
        'verification_token' => '1234',
        'verified_at' => null,
    ]);

    // When we POST to /verify with code 1111 (fixed test OTP)
    $resp = $this->postJson('/verify', [
        'email' => $user->email,
        'code' => '1111',
    ]);

    // Then it should be accepted in testing env and user verified
    $resp->assertStatus(200)
        ->assertJsonStructure(['message','user']);

    $user->refresh();
    expect($user->verified_at)->not->toBeNull();
});

