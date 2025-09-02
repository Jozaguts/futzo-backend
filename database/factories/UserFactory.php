<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Support\Fake;
use Illuminate\Support\Str;
use Random\RandomException;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;


    /**
     * @throws RandomException
     */
    public function definition(): array
    {
        return [
            'name' => $name = Fake::firstName(),
            'last_name' => Fake::lastName(),
            'email' => Fake::safeEmail(),
            'phone' => '+52 ' . Fake::numerify('322 ### ## ##'),
            'verified_at' => now(),
            'image' => 'https://ui-avatars.com/api/?name=' . $name,
            'verification_token' => random_int(1000, 9999),
            'password' => '$2y$10$RENqDsgT5rr0sjujwq1v4uoTXC9K9f7KMa1ilMFOdG2DMf7Xwm2TS', // password.
            'remember_token' => Str::random(10),
            'status' => User::PENDING_ONBOARDING_STATUS,
            'trial_ends_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'verified_at' => null,
        ]);
    }
}
