<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        $categories =[
            [
                'name' => 'Amateur',
                'age_range' => '*',
                'gender' => 'male',
            ],
            [
                'name' => 'Ascenso',
                'age_range' => '*',
                'gender' => 'male',
            ],
            [
                'name' => 'Especial',
                'age_range' => '*',
                'gender' => 'male',
            ],
        ];

        foreach($categories as $category) {
            DB::table('categories')->insert($category);
        }
    }
}
