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
        /*
         * Genders
         * Varonil 1
         * Femenil 2
         * */
        $categories = [
            [
                'name' => 'amateur',
                'gender_id' => 1
            ],
            [
                'name' => 'amateur',
                'gender_id' => 2
            ],
            [
                'name' => 'asenso',
                'gender_id' => 1
            ],
            [
                'name' => 'asenso',
                'gender_id' => 2
            ],
            [
                'name' => 'especial',
                'gender_id' => 1
            ],
            [
                'name' => 'especial',
                'gender_id' => 2
            ],
            [
                'name' => 'veteranos',
                'gender_id' => 1
            ],
            [
                'name' => 'veteranos',
                'gender_id' => 2
            ],
        ];

        foreach($categories as $category) {
            DB::table('categories')->insert($category);
        }
    }
}
