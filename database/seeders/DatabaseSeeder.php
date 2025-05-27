<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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
            CountriesSeeder::class,
            PositionsTableSeeder::class,
            CategoriesTableSeeder::class,
            TournamentFormatTableSeeder::class,
            FootballTypesTableSeeder::class,
            DefaultTournamentConfigurationTableSeeder::class,
            ActionsTableSeeder::class,
            CouponsTableSeeder::class,
            PhasesTableSeeder::class,
            LeaguesTableSeeder::class,
            AdminUserSeeder::class,
            LocationsTableSeeder::class,
            FieldsTableSeeder::class,
            TournamentTableSeeder::class,
            TeamsTableSeeder::class,
        ]);
//        $this->call(LeagueFieldTableSeeder::class);

//        $this->call(UserSeeder::class);
//        $this->call(TournamentTableSeeder::class);
//        $this->call(TeamsTableSeeder::class);
//        $this->call(PlayerSeeder::class);
//        $this->call(GameSeeder::class);
//        $this->call(GamePlayerSeeder::class);
//

    }
}
