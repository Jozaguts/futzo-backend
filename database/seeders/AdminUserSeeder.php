<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Sagit Gutierrez',
            'email' => 'admin@futzo.io',
            'verified_at' => now(),
            'verification_token' => random_int(1000, 9999),
            'phone' => '3221231231',
            'password' => '$2y$10$RENqDsgT5rr0sjujwq1v4uoTXC9K9f7KMa1ilMFOdG2DMf7Xwm2TS', //password.
            'remember_token' => Str::random(10),
        ]);
        $user->assignRole('super administrador');
        $user->league_id = League::first()->id;
        $user->saveQuietly();
    }
}
