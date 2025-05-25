<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\ActionsTableSeeder;
use Database\Seeders\CategoriesTableSeeder;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CouponsTableSeeder;
use Database\Seeders\DefaultTournamentConfigurationTableSeeder;
use Database\Seeders\FootballTypesTableSeeder;
use Database\Seeders\LeaguesTableSeeder;
use Database\Seeders\LocationsTableSeeder;
use Database\Seeders\PositionsTableSeeder;
use Database\Seeders\TournamentFormatTableSeeder;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

trait InitUser
{
    public User|null $user = null;

    public function initUser(): User
    {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
        $this->user->assignRole('super administrador');
        return $this->user;
    }

    public function addLeague(): User
    {
        $this->user->league_id = 1;
        $this->user->save();
        return $this->user;
    }
}
