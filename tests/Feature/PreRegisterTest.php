<?php

namespace Tests\Feature;


it('pre-registers a user correctly', function () {

    $email = fake()->email;

    $response = $this->postJson('/api/v1/pre-register', ['email' => $email]);

    $response->assertStatus(201);
});
