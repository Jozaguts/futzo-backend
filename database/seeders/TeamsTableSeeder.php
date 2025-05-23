<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = fake();

        for ($i = 1; $i <= 16; $i++) {
            // Crear coach (director técnico) y presidente (delegado) con emails de Faker
            $dt = User::factory()->create([
                'email' => $faker->unique()->safeEmail,
                'league_id' => 1,
            ]);

            $delegado = User::factory()->create([
                'email' => $faker->unique()->safeEmail,
                'league_id' => 1,
            ]);

            // Crear equipo con datos realistas
            $team = Team::factory()->create([
                'name' => $faker->lastName . '-' . $faker->lastName . ' FC',
                'email' => $faker->unique()->safeEmail,
                'phone' => '+52 ' . $faker->numerify('322 ### ## ##'),
                'address' => [
                    'terms' => [
                        ['value' => $faker->streetName, 'offset' => 0],
                        ['value' => $faker->city, 'offset' => 0],
                        ['value' => $faker->country, 'offset' => 0],
                    ],
                    'types' => ['establishment', 'point_of_interest'],
                    'place_id' => $faker->uuid,
                    'reference' => $faker->uuid,
                    'description' => $faker->streetAddress . ', ' . $faker->city . ', ' . $faker->country,
                    'matched_substrings' => [['length' => 5, 'offset' => 0]],
                    'structured_formatting' => [
                        'main_text' => $faker->streetName,
                        'secondary_text' => $faker->city . ', ' . $faker->country,
                        'main_text_matched_substrings' => [['length' => 5, 'offset' => 0]],
                    ],
                ],
                'colors' => [
                    'away' => ['short' => $faker->hexColor, 'jersey' => $faker->hexColor],
                    'home' => ['short' => $faker->hexColor, 'jersey' => $faker->hexColor],
                ],
                'description' => $faker->sentence,
                'president_id' => $delegado->id,
                'coach_id' => $dt->id,
            ]);

            $team->tournaments()->attach(1);
            $team->leagues()->attach(1);
            $team->categories()->attach(1);
        }
    }
}
