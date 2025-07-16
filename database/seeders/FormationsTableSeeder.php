<?php

namespace Database\Seeders;

use App\Models\Formation;
use Illuminate\Database\Seeder;

class FormationsTableSeeder extends Seeder
{
    public function run(): void
    {
        Formation::insert([
            ['value' => '4-4-2'],
            ['value' => '4-3-3'],
            ['value' => '4-5-1'],
            ['value' => '3-5-2'],
            ['value' => '4-1-2-1-2'],
            ['value' => '4-2-3-1'],
            ['value' => '4-4-1-1'],
            ['value' => '4-1-3-2'],
            ['value' => '3-4-3'],
            ['value' => '5-4-1'],
            ['value' => '3-5-1-1'],
            ['value' => '4-1-4-1'],
            ['value' => '4-3-1-2'],
            ['value' => '4-1-2-3'],
            ['value' => '5-3-2'],
        ]);
    }
}