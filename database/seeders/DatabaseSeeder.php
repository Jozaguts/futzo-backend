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
      $league = League::create([
            'name' => 'Liga de futbol',
            'description' => 'Liga de futbol de la ciudad de Medellin',
            'creation_date' => '2021-01-01',
            'logo' => 'https://ui-avatars.com/api/?name=Liga+de+futbol&size=64',
            'status' => 'Activa',
        ]);
       $user->league_id = $league->id;
       $user->assignRole('super administrador');
        \App\Models\User::factory()->count(175)->create();
        $this->call(PositionsTableSeeder::class);
        $this->call(GendersTableSeeder::class);
        $this->call(CategoriesTableSeeder::class);
        $this->call(TournamentTableSeeder::class);
        $this->call(TeamsTableSeeder::class);
        $this->call(ActionsTableSeeder::class);
        $players = Player::factory()
            ->count(175)
            ->state(new Sequence(
                ['team_id' => 1,],
                ['team_id' => 2,],
                ['team_id' => 3,],
                ['team_id' => 4,],
                ['team_id' => 5,],
                ['team_id' => 6,],
                ['team_id' => 7,],

            ))
            ->create();
    }
}
