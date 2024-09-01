<?php

namespace Tests\Feature;

use Tests\TestCase;

class PreRegisterTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_pre_register(): void
    {
        $email = fake()->email;

        $response = $this->postJson('/api/v1/pre-register',['email' => $email]);

        $response->assertStatus(201);
    }
}
