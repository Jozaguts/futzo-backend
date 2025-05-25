<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Random\RandomException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * @throws RandomException
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
        ]);
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
        $this->call(CountriesSeeder::class);
        $this->call(PositionsTableSeeder::class);
        $this->call(CategoriesTableSeeder::class);
        $this->call(TournamentFormatTableSeeder::class);
        $this->call(FootballTypesTableSeeder::class);
        $this->call(DefaultTournamentConfigurationTableSeeder::class);
        $this->call(ActionsTableSeeder::class);
        $this->call(CouponsTableSeeder::class);
        $this->call(LeaguesTableSeeder::class);
        $this->call(LocationsTableSeeder::class);
        $this->call(FieldsTableSeeder::class);
//        $this->call(LeagueFieldTableSeeder::class);
        $user->league_id = League::first()->id;
        $user->save();
//        $this->call(UserSeeder::class);
        $this->call(TournamentTableSeeder::class);
        $this->call(TeamsTableSeeder::class);
//        $this->call(PlayerSeeder::class);
//        $this->call(GameSeeder::class);
//        $this->call(GamePlayerSeeder::class);
//

    }
}
