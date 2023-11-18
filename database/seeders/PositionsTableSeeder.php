<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $positions =[
            [
                'type'=> 'portero',
                'name'=>'portero',
                'abbr' =>'por',
            ],
            [
                'type'=> 'defensa',
                'name'=>'carrilero',
                'abbr' =>'car',
            ],
            [
                'type'=> 'defensa',
                'name'=>'libero',
                'abbr' =>'lib',
            ],
            [
                'type'=> 'defensa',
                'name'=>'central',
                'abbr' =>'cen',
            ],
            [
                'type'=> 'defensa',
                'name'=>'lateral',
                'abbr' =>'lat',
            ],
            [
                'type'=> 'medio',
                'name'=> 'pivote',
                'abbr' =>'piv',
            ],
            [
                'type'=> 'medio',
                'name'=> 'interior',
                'abbr' =>'int',
            ],
            [
                'type'=> 'medio',
                'name'=> 'media punta',
                'abbr' =>'mep',
            ],
            [
                'type'=> 'medio',
                'name'=> 'volante',
                'abbr' =>'vol',
            ],
            [
                'type'=> 'delantero',
                'name'=> 'extremo',
                'abbr' =>'ext',
            ],
            [
                'type'=> 'delantero',
                'name'=> 'delantero centro',
                'abbr' =>'dec',
            ],
            [
                'type'=> 'delantero',
                'name'=> 'segundo delantero',
                'abbr' =>'des',
            ],
        ];

        foreach($positions as $position){
            DB::table('positions')->insert($position);
        }
    }
}
