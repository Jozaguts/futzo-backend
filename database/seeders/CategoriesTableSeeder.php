<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        $categories = config('constants.categories');

        foreach ($categories as $category) {
            DB::table('categories')->insert($category);
        }
    }
}
