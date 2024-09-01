<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
       $url = 'https://ui-avatars.com/api/?name=';
       $teams = [
            [
                'name' => 'Team 1',
                'president_id' => null,
                'coach_id' => null,
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
                'address' => fake()->address,
                'image' => $url . 'Team+1&size=64',
                'colors' => json_encode([
                    'home' => [
                        'jersey' => 'red',
                        'short' => 'red',
                    ],
                    'away' => [
                        'jersey' => 'blue',
                        'short' => 'blue',
                    ],
                ]),
            ],
           [
               'name' => 'Team 2',
               'president_id' => null,
               'coach_id' => null,
               'phone' => fake()->phoneNumber,
               'email' => fake()->email,
               'address' => fake()->address,
               'image' => $url . 'Team+2&size=64',
               'colors' => json_encode([
                   'home' => [
                       'jersey' => 'green',
                       'short' => 'green',
                   ],
                   'away' => [
                       'jersey' => 'yellow',
                       'short' => 'yellow',
                   ],
               ]),
           ]
        ];

        foreach($teams as $team){
           DB::table('teams')->insert($team);
        }
    }
}
