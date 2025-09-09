<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Random\RandomException;

class DatabaseSeeder extends Seeder
{
//    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * @throws RandomException
     */
    public function run(): void
    {
        $seeders  = [
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
        ];
        if (app()->environment('local')) {
            $seeders = [
               ...$seeders,
                LeaguesTableSeeder::class,
                AdminUserSeeder::class,
                LocationsTableSeeder::class,
//                FieldsTableSeeder::class,
                // Ventanas base 24/7 por campo y por liga-campo
//                FieldWindowsSeeder::class,
//                LeagueFieldWindowsSeeder::class,
//                TournamentTableSeeder::class,
//                FormationsTableSeeder::class,
//                TeamsTableSeeder::class,
            ];
        }
        $this->call($seeders);
        Artisan::call('sync:stripe-prices');
    }
}
