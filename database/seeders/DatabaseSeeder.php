<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\League;
use App\Models\Player;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
       $this->call([
           RolesTableSeeder::class,
       ]);
       $user = User::create([
            'name' => 'Sagit',
            'lastname' => 'Gutierrez',
            'email' => 'admin@sls.com',
            'email_verified_at' => now(),
            'phone' => '3221231231',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ]);

        $user->assignRole('super administrador');
        $this->call(LeaguesTableSeeder::class);
        $user->league_id = League::first()->id;
        $user->save();

        $this->call(PositionsTableSeeder::class);
//        $this->call(GendersTableSeeder::class);
        $this->call(CategoriesTableSeeder::class);
        $this->call(TournamentTableSeeder::class);
        $this->call(TeamsTableSeeder::class);
        $this->call(ActionsTableSeeder::class);
//        Player::factory()
//            ->count(3)
//            ->state(new Sequence(
//                ['team_id' => 1,],
//                ['team_id' => 2,],
//                ['team_id' => 3,],
//                ['team_id' => 4,],
//                ['team_id' => 5,],
//                ['team_id' => 6,],
//                ['team_id' => 7,],
//
//            ))
//            ->create();
    }
}
