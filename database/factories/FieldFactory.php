<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FieldFactory extends Factory
{
    protected $model = Field::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'type' => Field::defaultType,
            'dimensions' => Field::defaultDimensions,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'location_id' => Location::factory()->id,
        ];
    }
}
