<?php

namespace Database\Seeders;

use App\Models\Action;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $actions = [
            'goal',
            'yellow card',
            'red card',
            'penalty',
        ];
        foreach ($actions as $action) {
            Action::create(['name' => $action]);
        }
    }
}
