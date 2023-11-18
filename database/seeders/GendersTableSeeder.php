<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GendersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('genders')->insert(['name'=> 'varonil','abbr' =>'v']);
        DB::table('genders')->insert(['name'=> 'femenil','abbr' =>'f']);
    }
}
