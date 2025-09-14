<?php

namespace Database\Seeders;

use App\Models\Phase;
use Illuminate\Database\Seeder;

class PhasesTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('constants.phases') as $phase) {
            Phase::factory()->create([
                'id' => $phase['id'],
                'name' => $phase['name'],
                'min_teams_for' => $phase['min_teams_for'],
                'is_active' => $phase['is_active'],
                'is_completed' => $phase['is_completed']
            ]);
        }
    }
}
