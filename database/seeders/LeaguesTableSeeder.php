<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaguesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $url = 'https://ui-avatars.com/api/?name=';
        $leagues = [
            [
                'name' => 'Liga MX',
                'location' => 'Mexico',
                'description' => 'La principal competición de fútbol profesional en México.',
                'logo' => $url . 'Liga+MX&size=64',
                'banner' => $url . 'Liga+MX&size=256',
                'status' => 'active',
                'creation_date' => '2021-10-10',
            ],
            [
                'name' => 'MLS',
                'location' => 'USA',
                'description' => 'Major League Soccer, la principal liga de fútbol en Estados Unidos.',
                'logo' => $url . 'MLS&size=64',
                'banner' => $url . 'MLS&size=256',
                'status' => 'active',
                'creation_date' => '2021-10-10',
            ]
        ];
        foreach ($leagues as $league) {
            DB::table('leagues')->insert($league);
        }
    }
}
