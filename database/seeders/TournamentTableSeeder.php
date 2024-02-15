<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $url = 'https://ui-avatars.com/api/?name=';
        $tournaments = [
            [
                'name' => 'Liga MX',
                'location' => 'Mexico',
                'start_date' => '2021-08-01',
                'end_date' => '2021-12-01',
                'prize' => '1000000',
                'winner' => null,
                'description' => 'Liga MX description',
                'logo' => $url . 'Liga+MX&size=64',
                'banner' =>$url . 'Liga+MX&size=256',
                'status' => 'active'
            ],
            [
                'name' => 'MLS',
                'location' => 'USA',
                'start_date' => '2021-08-01',
                'end_date' => '2021-12-01',
                'prize' => '1000000',
                'winner' => null,
                'description' => 'MLS description',
                'logo' => $url . 'MLS&size=64',
                'banner' =>$url . 'MLS&size=256',
                'status' => 'active'
            ],
        ];
        DB::table('tournaments')->insert($tournaments);
    }
}
