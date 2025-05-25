<?php

use Database\Seeders\CouponsTableSeeder;

it('stores a pre register correctly', function () {
    $this->seed(CouponsTableSeeder::class);
    $email = fake()->email;
    $response = $this->postJson('/api/v1/pre-register', ['email' => $email]);

    $response->assertStatus(201);

});
