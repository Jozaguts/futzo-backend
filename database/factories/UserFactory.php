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
    protected static array $reservedEmails = [];
    protected static array $reservedPhones = [];


    /**
     * @throws RandomException
     */
    public function definition(): array
    {
        $email = $this->uniqueEmail();
        $phone  = $this->uniquePhone();
        return [
            'name' => $name = Fake::firstName(),
            'last_name' => Fake::lastName(),
            'email' => $email,
            'phone' => $phone,
            'verified_at' => now(),
            'image' => 'https://ui-avatars.com/api/?name=' . $name,
            'verification_token' => random_int(1000, 9999),
            'password' => '$2y$10$RENqDsgT5rr0sjujwq1v4uoTXC9K9f7KMa1ilMFOdG2DMf7Xwm2TS', // password.
            'remember_token' => Str::random(10),
            'status' => User::PENDING_ONBOARDING_STATUS,
            'plan' => config('billing.default_plan', User::PLAN_FREE),
            'tournaments_quota' => config('billing.plans.free.tournaments_quota', 1),
            'tournaments_used' => 0,
            'plan_started_at' => now(),
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

    private function uniqueEmail(): string
    {
        // Garantiza unicidad contra BD y dentro del proceso
        for ($i = 0; $i < 50; $i++) {
            $email = Fake::safeEmail();
            if (!isset(self::$reservedEmails[$email]) && !User::where('email', $email)->exists()) {
                self::$reservedEmails[$email] = true;
                return $email;
            }
        }
        // Fallback casi imposible de colisionar
        $email = 'user+'.Str::uuid().'@example.com';
        self::$reservedEmails[$email] = true;
        return $email;
    }

    private function uniquePhone(): string
    {
        for ($i = 0; $i < 50; $i++) {
            $phone = '+52 ' . Fake::numerify('### ### ## ##');
            if (!isset(self::$reservedPhones[$phone]) && !User::where('phone', $phone)->exists()) {
                self::$reservedPhones[$phone] = true;
                return $phone;
            }
        }
        // Fallback con sufijo único
        $phone = '+52 ' . Fake::numerify('### ### ## ##') . ' ' . substr((string) Str::uuid(), 0, 4);
        self::$reservedPhones[$phone] = true;
        return $phone;
    }
}
