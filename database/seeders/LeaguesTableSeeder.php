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

        League::factory()->count(3)->create();
    }
}
