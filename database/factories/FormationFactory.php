<?php

namespace Database\Factories;

use App\Models\Formation;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Support\Fake;
use Illuminate\Support\Carbon;

class FormationFactory extends Factory
{
    protected $model = Formation::class;

    public function definition(): array
    {
        return [
            'value' => Fake::randomElement([
                '4-4-2',
                '4-4-3',
                '4-4-4',
                '4-5-1',
                '4-1-2-1-2',
                '4-1-3-2',
                '5-4-1',
                '4-1-4-1',
                '5-3-2'
            ]),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
