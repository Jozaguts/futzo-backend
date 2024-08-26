<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\League;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
           RolesTableSeeder::class,
       ]);
       $user = User::create([
            'name' => 'Sagit Gutierrez',
            'email' => 'admin@futzo.io',
            'email_verified_at' => now(),
            'email_verification_token' => Str::random(25),
            'phone' => '3221231231',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);
       $user->assignRole('super administrador');
       $this->call(FootballTypesTableSedder::class);
       $this->call(LeaguesTableSeeder::class);
       $user->league_id = League::first()->id;
       $user->save();
       $this->call(PositionsTableSeeder::class);
       $this->call(CategoriesTableSeeder::class);
       $this->call(TournamentFormatTableSeeder::class);
       $this->call(LocationTableSeeder::class);
       $this->call(TournamentTableSeeder::class);
       $this->call(LocationTournamentTableSeeder::class);
       $this->call(TeamsTableSeeder::class);
       $this->call(ActionsTableSeeder::class);
       $this->call(CouponsTableSeeder::class);

    }
}
