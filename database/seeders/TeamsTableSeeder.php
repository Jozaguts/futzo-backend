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
       $address = json_encode([
           'description' => 'La Sabana, San José Province, San José, Sabana, Costa Rica',
           'matched_substrings' => [
               [
                   'length' => 9,
                   'offset' => 0
               ]
           ],
           'place_id' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
           'reference' => 'ChIJM_Dtpqv8oI8RyETi6jXqf_c',
           'structured_formatting' => [
               'main_text' => 'La Sabana',
               'main_text_matched_substrings' => [
                   [
                       'length' => 9,
                       'offset' => 0
                   ]
               ],
               'secondary_text' => 'San José Province, San José, Sabana, Costa Rica'
           ],
           'terms' => [
               [
                   'offset' => 0,
                   'value' => 'La Sabana'
               ],
               [
                   'offset' => 11,
                   'value' => 'San José Province'
               ],
               [
                   'offset' => 30,
                   'value' => 'San José'
               ],
               [
                   'offset' => 40,
                   'value' => 'Sabana'
               ],
               [
                   'offset' => 48,
                   'value' => 'Costa Rica'
               ]
           ],
           'types' => [
               'establishment',
               'tourist_attraction',
               'point_of_interest',
               'park'
           ]
       ]);
       $teams = [
            [
                'name' => 'Team 1',
                'president_id' => null,
                'coach_id' => null,
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
                'address' => $address,
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
               'address' => $address,
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
          $id = DB::table('teams')->insert($team);
        }
        DB::table('league_team')->insert([
            'team_id' => 1,
            'league_id' => 1
        ]);
        DB::table('league_team')->insert([
            'team_id' => 2,
            'league_id' => 2
        ]);
    }
}
