<?php

namespace Database\Seeders;

use App\Models\League;
use Illuminate\Database\Seeder;

class LeaguesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        $url = 'https://ui-avatars.com/api/?name=';
        League::factory()->count(3)->create();
    }
}
