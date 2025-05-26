<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\League;
use App\Models\LeagueField;
use App\Models\Location;
use Illuminate\Database\Seeder;

class FieldsTableSeeder extends Seeder
{
    public function run(): void
    {

        $leagueId = League::first()->id;
        // Disponibilidad por defecto definida en config/constants.php bajo 'availability'
        $defaultAvailability = config('constants.availability');

        // Para cada ubicación, crear 2 campos y su relación con la liga
        Location::all()->each(function (Location $location) use ($leagueId, $defaultAvailability) {
            for ($n = 1; $n <= 2; $n++) {
                // Crear el campo
                $field = Field::create([
                    'location_id' => $location->id,
                    'name' => "Campo {$n}",
                    'type' => 'Fútbol 11',
                    'dimensions' => ['width' => 90, 'length' => 120],
                ]);

                // Crear la relación league_field
                LeagueField::create([
                    'league_id' => $leagueId,
                    'field_id' => $field->id,
                    'availability' => $defaultAvailability,
                ]);
            }
        });
    }
}
