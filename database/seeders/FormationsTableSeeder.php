<?php

namespace Database\Seeders;

use App\Models\Formation;
use Illuminate\Database\Seeder;

class FormationsTableSeeder extends Seeder
{
    public function run(): void
    {
        Formation::insert([
            [
                'name' => '4-4-2',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 4,
                'forwards' => 2
            ],
            [
                'name' => '4-3-3',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 3,
                'forwards' => 3
            ],
            [
                'name' => '4-5-1',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 5,
                'forwards' => 1
            ],
            [
                'name' => '3-5-2',
                'goalkeeper' => 1,
                'defenses' => 3,
                'midfielders' => 5,
                'forwards' => 2
            ],
            [
                'name' => '4-1-2-1-2',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 4,
                'forwards' => 2
            ],
            [
                'name' => '4-2-3-1',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 5,
                'forwards' => 1
            ],
            [
                'name' => '4-4-1-1',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 4,
                'forwards' => 2
            ],
            [
                'name' => '4-1-3-2',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 4,
                'forwards' => 2
            ],
            [
                'name' => '3-4-3',
                'goalkeeper' => 1,
                'defenses' => 3,
                'midfielders' => 4,
                'forwards' => 3
            ],
            [
                'name' => '5-4-1',
                'goalkeeper' => 1,
                'defenses' => 5,
                'midfielders' => 4,
                'forwards' => 1
            ],
            [
                'name' => '3-5-1-1',
                'goalkeeper' => 1,
                'defenses' => 3,
                'midfielders' => 5,
                'forwards' => 2
            ],
            [
                'name' => '4-1-4-1',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 5,
                'forwards' => 1
            ],
            [
                'name' => '4-3-1-2',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 4,
                'forwards' => 2
            ],
            [
                'name' => '4-1-2-3',
                'goalkeeper' => 1,
                'defenses' => 4,
                'midfielders' => 3,
                'forwards' => 3
            ],
            [
                'name' => '5-3-2',
                'goalkeeper' => 1,
                'defenses' => 5,
                'midfielders' => 3,
                'forwards' => 2
            ]
        ]);
    }
}