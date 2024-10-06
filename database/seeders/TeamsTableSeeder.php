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
        $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';// password
        $expectedColors = json_encode([
            'home' => [
                'primary' => '#9155FD',
                'secondary' => '#9155FD',
            ],
            'away' => [
                'primary' => '#9155FD',
                'secondary' => '#9155FD',
            ],
        ]);
        $teamsData = [
            [
                'team' => [
                    'name' => 'Team 1',
                    'address' => $address,
                    'email' => fake()->email,
                    'phone' => '+52 322 2392929',
                    'description' => 'Team 1 description',
                    'image' => $url . 'team1',
                    'president_id' => 1,
                    'coach_id' => 2,
                    'colors' => $expectedColors,
                ],
                'president' => [
                    'name' => fake()->name('male'),
                    'email' => fake()->email,
                    'phone' => '+52 322 2392921',
                    'password' => $password,
                    'remember_token' => '1234',
                    'email_verified_at' => now(),
                    'image' => $url . 'president',
                ],
                'coach' => [
                    'name' => fake()->name,
                    'email' => fake()->email,
                    'phone' => '+52 322 2392922',
                    'password' => $password,
                    'remember_token' => '1234',
                    'email_verified_at' => now(),
                    'image' => $url . 'coach',
                ]
            ]
        ];


        foreach ($teamsData as $teamData) {
            $president = collect($teamData['president']);
            $coach = collect($teamData['coach']);
            $team = collect($teamData['team']);
            $presidentId = DB::table('users')->insertGetId($president->toArray());
            $coachId = DB::table('users')->insertGetId($coach->toArray());
            $teamId = DB::table('teams')->insertGetId([...$team->toArray(), 'president_id' => $presidentId, 'coach_id' => $coachId]);
            DB::table('league_team')->insert([
                'league_id' => 1,
                'team_id' => $teamId
            ]);
            DB::table('category_team')->insert([
                'category_id' => 1,
                'team_id' => $teamId,
            ]);
            DB::table('team_tournament')->insert([
                'team_id' => $teamId,
                'tournament_id' => 1,
            ]);

        }
    }
}
