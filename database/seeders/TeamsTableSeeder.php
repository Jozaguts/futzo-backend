<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $teams = [
            [
                'name'=> 'Cruz azul',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 1,
            ],
            [
                'name'=> 'America',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 1,
            ],
            [
                'name'=> 'Chivas',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 1,
            ],
            [
                'name'=> 'Pumas',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 1,
            ],
            [
                'name'=> 'Toluca',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 1,
            ],
            [
                'name'=> 'Santos',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 1,
            ],
            [
                'name'=> 'Pachuca',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 1,
            ],
            // add teams of MLS league
            [
                'name'=> 'LA Galaxy',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 2,
            ],
            [
                'name'=> 'LAFC',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 2,
            ],
            [
                'name'=> 'Seattle Sounders',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 2,
            ],
            [
                'name'=> 'Portland Timbers',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 2,
            ],
            [
                'name'=> 'New York City FC',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 2,
            ],
            [
                'name'=> 'Atlanta United',
                'group'=> null,
                'won'=> 0,
                'draw'=> 0,
                'lost'=> 0,
                'goals_against'=> 0,
                'goals_for'=> 0,
                'goals_difference'=> 0,
                'points'=> 0,
                'category_id'=> 1,
                'tournament_id' => 2,
            ]
        ];

        foreach($teams as $team){
            DB::table('teams')->insert($team);
        }
    }
}
