<?php

namespace Database\Seeders;

use App\Models\LeagueField;
use Illuminate\Database\Seeder;

class LeagueFieldTableSeeder extends Seeder
{
    public function run(): void
    {
        LeagueField::factory()->count(3)->create();
    }
}
