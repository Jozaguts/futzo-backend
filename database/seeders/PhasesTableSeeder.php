<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PhasesTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('constants.phases') as $phase) {
            \App\Models\Phase::factory()->create([
                'id' => $phase['id'],
                'name' => $phase['name'],
            ]);
        }
    }
}
