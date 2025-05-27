<?php

namespace Database\Factories;

use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PhaseFactory extends Factory
{
    protected $model = Phase::class;

    public function definition(): array
    {
        $phase = config('constants.phases')[0];
        return [
            'id' => $phase['id'],
            'name' => $phase['name'],
        ];
    }
}
